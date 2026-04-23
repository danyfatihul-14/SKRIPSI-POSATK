<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

class KasirController extends Controller
{
    public function __construct()
    {
        // Setup Midtrans config
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function index()
    {
        return view('kasir.index');
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');

        $query = Product::query();

        if (! empty($q)) {
            $query->where('product_name', 'like', "%$q%");
        }

        $products = $query->get()->map(function ($product) {
            return [
                'product_id'     => $product->product_id,
                'product_name'   => $product->product_name,
                'selling_price'  => $product->selling_price,
                'unit'           => $product->unit,
                'original_price' => $product->original_price ?? null,
                'discount'       => $product->discount ?? null,
                'rating'         => $product->rating ?? 0,
                'image_url'      => $product->file_url
                    ? asset('storage/' . $product->file_url)
                    : null,
            ];
        });

        return response()->json($products);
    }

    public function storeOrder(Request $request)
    {
        abort_unless(in_array($request->user()?->role, ['pelayan', 'cashier'], true), 403);

        $payload = $request->validate([
            'cart' => ['required', 'string'],
            'payment' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris'],
        ]);

        $cart = json_decode($payload['cart'], true);
        if (!is_array($cart) || empty($cart)) {
            return response()->json([
                'message' => 'Keranjang kosong atau tidak valid.'
            ], 422);
        }

        $items = $this->buildCartItems($cart);
        $subtotal = collect($items)->sum('subtotal');

        $transaction = DB::transaction(function () use ($request, $items, $subtotal) {
            $transaction = Transaction::create([
                'store_id' => $request->user()->store_id,
                'user_id' => $request->user()->user_id,
                'subtotal' => $subtotal,
                'tax' => 0,
                'discount' => 0,
                'total_amount' => $subtotal,
                'status' => 'pending_payment',
                'payment_method' => null,
                'customer_type' => 'walkin',
                'notes' => 'Order dibuat oleh pelayan',
            ]);

            foreach ($items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->transaction_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $transaction;
        });

        return response()->json([
            'message' => 'Pesanan berhasil dikirim ke kasir.',
            'transaction_id' => $transaction->transaction_id,
            'transaction_code' => $transaction->transaction_code,
        ]);
    }

    public function generateQris(Request $request)
    {
        abort_unless($request->user()?->role === 'cashier', 403);

        $request->validate([
            'cart' => ['required', 'string'],
            'transaction_id' => ['nullable', 'numeric'],
        ]);

        $cart = json_decode($request->input('cart'), true);
        if (!is_array($cart) || empty($cart)) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang tidak valid.',
            ]);
        }

        try {
            $items = $this->buildCartItems($cart);

            $subtotal = (int) round(collect($items)->sum('subtotal'));
            $qrisFee = (int) ceil($subtotal * 0.007);
            $grossAmount = $subtotal + $qrisFee;

            if ($grossAmount < 1000) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Minimal pembayaran QRIS Rp 1.000.',
                ]);
            }

            $transactionId = $request->input('transaction_id') ?? time();
            $orderId = 'ORDER-' . $transactionId . '-' . time();

            $transactionDetails = [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ];

            $customerDetails = [
                'email' => $request->user()->email ?? 'kasir@pos.local',
                'phone' => $request->user()->phone ?? '08123456789',
            ];

            $itemDetails = collect($items)->map(function ($item) {
                return [
                    'id' => (string) $item['product_id'],
                    'price' => (int) $item['unit_price'],
                    'quantity' => (int) $item['quantity'],
                    'name' => "Product {$item['product_id']}",
                ];
            })->values()->all();

            // Tambahkan item fee QRIS supaya nilai item_details = gross_amount
            $itemDetails[] = [
                'id' => 'FEE-QRIS',
                'price' => $qrisFee,
                'quantity' => 1,
                'name' => 'Biaya Layanan QRIS 0.7%',
            ];

            $params = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails,
                'item_details' => $itemDetails,
                // 'enabled_payments' => ['gopay'],
            ];

            $snapToken = Snap::getSnapToken($params);

            return response()->json([
                'status' => 'success',
                'snap_token' => $snapToken,
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function checkout(Request $request)
    {
        abort_unless($request->user()?->role === 'cashier', 403);

        $payload = $request->validate([
            'cart' => ['required', 'string'],
            'payment' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris'],
        ]);

        $cart = json_decode($payload['cart'], true);

        if (! is_array($cart) || count($cart) === 0) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang kosong atau tidak valid.',
            ]);
        }

        $items = $this->buildCartItems($cart);
        $subtotal = collect($items)->sum('subtotal');
        $payment = (float) $payload['payment'];

        // QRIS: Payment otomatis sesuai total (tidak perlu harus sama persis)
        if ($payload['payment_method'] === 'qris') {
            $payment = $subtotal; // Override ke total untuk QRIS
        } else {
            // Cash/Transfer: harus lebih dari atau sama dengan total
            if ($payment < $subtotal) {
                throw ValidationException::withMessages([
                    'payment' => 'Nominal pembayaran kurang dari total belanja.',
                ]);
            }
        }

        $transaction = DB::transaction(function () use ($request, $items, $subtotal, $payload) {
            $transaction = Transaction::create([
                'store_id' => $request->user()->store_id,
                'user_id' => $request->user()->user_id,
                'subtotal' => $subtotal,
                'tax' => 0,
                'discount' => 0,
                'total_amount' => $subtotal,
                'status' => 'completed',
                'payment_method' => $payload['payment_method'],
                'customer_type' => 'walkin',
                'notes' => 'Transaksi dibayar oleh kasir',
            ]);

            foreach ($items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->transaction_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $transaction;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Transaksi berhasil disimpan.',
                'transaction_id' => $transaction->transaction_id,
                'transaction_code' => $transaction->transaction_code,
                'receipt_url' => route('kasir.receipt', $transaction),
            ]);
        }

        return redirect()
            ->route('kasir.index')
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    // ✨ NEW METHOD: Webhook dari Midtrans untuk verifikasi payment
    public function midtransNotification(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $orderId = $request->order_id;

        if ($request->transaction_status === 'settlement' || $request->transaction_status === 'capture') {
            return response()->json(['status' => 'success']);
        } elseif ($request->transaction_status === 'pending') {
            return response()->json(['status' => 'success']);
        } elseif ($request->transaction_status === 'deny' || $request->transaction_status === 'cancel' || $request->transaction_status === 'expire') {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'success']);
    }

    private function buildCartItems(array $rawCart): array
    {
        $productIds = collect($rawCart)
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        $products = Product::whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        $items = collect($rawCart)
            ->map(function ($row) use ($products) {
                $product = $products->get($row['id'] ?? null);

                if (! $product) {
                    return null;
                }

                $quantity = (int) ($row['qty'] ?? 0);

                if ($quantity < 1) {
                    return null;
                }

                $unitPrice = (float) $product->selling_price;
                $subtotal = $quantity * $unitPrice;

                return [
                    'product_id' => $product->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => 0,
                    'subtotal' => $subtotal,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang kosong atau tidak valid.',
            ]);
        }

        return $items;
    }

    // Existing methods (pendingList, payPending, dll) tetap sama...
    public function pendingList(Request $request)
    {
        abort_unless($request->user()?->role === 'cashier', 403);

        $rows = Transaction::query()
            ->where('status', 'pending_payment')
            ->with([
                'user:user_id,name',
                'items.product:product_id,product_name',
            ])
            ->latest('created_at')
            ->get()
            ->map(function ($trx) {
                return [
                    'transaction_id' => $trx->transaction_id,
                    'transaction_code' => $trx->transaction_code,
                    'served_by' => $trx->user?->name ?? '-',
                    'total_amount' => (float) $trx->total_amount,
                    'created_at' => optional($trx->created_at)->format('d/m/Y H:i'),
                    'items' => $trx->items->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product?->product_name ?? 'Produk',
                            'qty' => (int) $item->quantity,
                            'price' => (float) $item->unit_price,
                            'subtotal' => (float) $item->subtotal,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json($rows);
    }

    public function payPending(Request $request, Transaction $transaction)
    {
        abort_unless($request->user()?->role === 'cashier', 403);

        if ($transaction->status !== 'pending_payment') {
            return response()->json(['message' => 'Transaksi bukan pending payment.'], 422);
        }

        $payload = $request->validate([
            'payment_method' => ['required', 'in:cash,qris'],
            'payment' => ['required', 'numeric', 'min:0'],
        ]);

        $total = (float) $transaction->total_amount;
        $payment = (float) $payload['payment'];

        if ($payload['payment_method'] !== 'qris' && $payment < $total) {
            return response()->json(['message' => 'Nominal pembayaran kurang dari total belanja.'], 422);
        }

        $transaction->update([
            'status' => 'completed',
            'payment_method' => $payload['payment_method'],
            'notes' => trim(($transaction->notes ?? '') . ' | Dibayar kasir: ' . now()->format('Y-m-d H:i:s')),
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil.',
            'transaction_id' => $transaction->transaction_id,
            'transaction_code' => $transaction->transaction_code,
            'receipt_url' => route('kasir.receipt', $transaction),
        ]);
    }

    public function deletePending(Request $request, Transaction $transaction)
    {
        abort_unless($request->user()?->role === 'cashier', 403);

        if ($transaction->status !== 'pending_payment') {
            return response()->json([
                'message' => 'Transaksi bukan pending payment.',
            ], 422);
        }

        DB::transaction(function () use ($transaction) {
            $transaction->items()->delete();
            $transaction->delete();
        });

        return response()->json([
            'message' => 'Transaksi pending berhasil dihapus.',
        ]);
    }

    public function receipt(Request $request, Transaction $transaction)
    {
        abort_unless(in_array($request->user()?->role, ['cashier', 'admin'], true), 403);

        $transaction->load([
            'user:user_id,name',
            'items.product:product_id,product_name',
        ]);

        return view('kasir.receipt', [
            'transaction' => $transaction,
        ]);
    }
}
