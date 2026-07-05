<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Log;
use Throwable;

class KasirController extends Controller
{
    public function __construct()
    {
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
        $storeId = $request->user()?->store_id;

        $query = Product::query()->with([
            'stockLevels' => fn($subQuery) => $subQuery->where('store_id', $storeId),
        ]);

        if ($storeId) {
            $query->whereHas('stockLevels', function ($subQuery) use ($storeId) {
                $subQuery->where('store_id', $storeId);
            });
        }

        if (! empty($q)) {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('product_name', 'like', "%$q%")
                    ->orWhere('variant', 'like', "%$q%");
            });
        }

        $products = $query->get()->map(function ($product) {
            $stockLevel = $product->stockLevels->first();

            return [
                'product_id'    => $product->product_id,
                'product_name'  => $product->product_name,
                'variant'       => $product->variant ?? '-',
                'unit'          => $product->unit ?? '-',
                'selling_price' => $product->selling_price ?? 0,
                'stock'         => (int) ($stockLevel?->quantity ?? 0),
                'image_url'     => $product->file_url ? asset('storage/' . $product->file_url) : null,
            ];
        });

        return response()->json($products);
    }

    public function storeOrder(Request $request)
    {
        try {
            $rawCart = $request->input('cart', []);
            $cart = is_string($rawCart) ? json_decode($rawCart, true) : $rawCart;

            if (! is_array($cart) || empty($cart)) {
                return response()->json(['message' => 'Keranjang tidak valid / kosong.'], 422);
            }

            $storeId = (int) ($request->user()?->store_id ?? 0);
            $items = $this->buildCartItems($cart, $storeId);
            $subtotal = (float) collect($items)->sum('subtotal');

            DB::transaction(function () use ($request, $items, $subtotal) {
                $transaction = Transaction::create([
                    'store_id'       => $request->user()->store_id,
                    'user_id'        => $request->user()->user_id,
                    'subtotal'       => $subtotal,
                    'tax'            => 0,
                    'discount'       => 0,
                    'total_amount'   => $subtotal,
                    'status'         => 'pending_payment',
                    'payment_method' => null,
                    'customer_type'  => 'walkin',
                    'customer_name'  => $request->input('customer_name') ?? 'Pelanggan Umum',
                    'notes'          => 'Order dibuat oleh [' . $request->user()->name . ']',
                ]);

                foreach ($items as $item) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->transaction_id,
                        'product_id'     => $item['product_id'],
                        'quantity'       => $item['quantity'],
                        'unit_price'     => $item['unit_price'],
                        'discount'       => $item['discount'],
                        'subtotal'       => $item['subtotal'],
                    ]);
                }

                $this->decrementStockLevels((int) $request->user()->store_id, $items);
            });

            return response()->json([
                'message' => 'Order berhasil dikirim ke waiting payment.',
            ]);
        } catch (Throwable $e) {
            Log::error('Kasir order gagal', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile(),
            ]);

            return response()->json([
                'message' => app()->isLocal() ? $e->getMessage() : 'Terjadi kesalahan saat menyimpan order.',
            ], 500);
        }
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

        $storeId = (int) ($request->user()?->store_id ?? 0);
        $items = $this->buildCartItems($cart, $storeId);

        $subtotal = (float) collect($items)->sum('subtotal');
        $payment = (float) $payload['payment'];

        if ($payload['payment_method'] === 'qris') {
            $payment = $subtotal;
        } else {
            if ($payment < $subtotal) {
                throw ValidationException::withMessages([
                    'payment' => 'Nominal pembayaran kurang dari total belanja.',
                ]);
            }
        }

        $transaction = DB::transaction(function () use ($request, $items, $subtotal, $payload) {
            $transaction = Transaction::create([
                'store_id'       => $request->user()->store_id,
                'user_id'        => $request->user()->user_id,
                'subtotal'       => $subtotal,
                'tax'            => 0,
                'discount'       => 0,
                'total_amount'   => $subtotal,
                'status'         => 'completed',
                'payment_method' => $payload['payment_method'],
                'customer_type'  => 'walkin',
                'customer_name'  => $request->input('customer_name') ?? 'Pelanggan Umum',
                'notes'          => 'Transaksi dibayar oleh [' . $request->user()->name . ']',
            ]);

            foreach ($items as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->transaction_id,
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'discount'       => $item['discount'],
                    'subtotal'       => $item['subtotal'],
                ]);
            }

            $this->decrementStockLevels((int) $request->user()->store_id, $items);

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

    private function buildCartItems(array $rawCart, ?int $storeId = null): array
    {
        $normalized = collect($rawCart)
            ->map(function ($row) {
                return [
                    'product_id' => (int) ($row['product_id'] ?? $row['id'] ?? 0),
                    'qty'        => (int) ($row['qty'] ?? 0),
                ];
            })
            ->filter(fn($row) => $row['product_id'] > 0 && $row['qty'] > 0)
            ->values();

        $productIds = $normalized->pluck('product_id')->unique()->values();

        $products = Product::query()
            ->with([
                'stockLevels' => fn($q) => $storeId
                    ? $q->where('store_id', $storeId)
                    : $q,
            ])
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        $items = $normalized->map(function ($row) use ($products) {
            $product = $products->get($row['product_id']);
            if (! $product) {
                return null;
            }

            $stockLevel = $product->stockLevels->first();
            $stock = (int) ($stockLevel?->quantity ?? 0);
            $qty = (int) $row['qty'];

            if ($qty < 1) {
                return null;
            }

            if ($qty > $stock) {
                throw ValidationException::withMessages([
                    'cart' => "Stok {$product->product_name} tidak cukup. Sisa stok: {$stock}.",
                ]);
            }

            $unitPrice = (float) ($product->selling_price ?? 0);
            $discount = (float) ($stockLevel?->discount ?? 0);
            $subtotal = ($qty * $unitPrice) - $discount;

            return [
                'product_id'   => (int) $product->product_id,
                'product_name' => (string) $product->product_name,
                'variant'      => (string) ($product->variant ?? '-'),
                'unit'         => (string) ($product->unit ?? '-'),
                'quantity'     => $qty,
                'unit_price'   => $unitPrice,
                'discount'     => $discount,
                'subtotal'     => max(0, $subtotal),
                'stock'        => $stock,
                'image_url'    => $product->file_url ? asset('storage/' . $product->file_url) : null,
            ];
        })->filter()->values()->all();

        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang kosong atau tidak valid.',
            ]);
        }

        return $items;
    }

    /**
     * Kurangi stok produk sesuai item transaksi.
     */
    private function decrementStockLevels(int $storeId, array $items): void
    {
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $stockLevel = StockLevel::query()
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stockLevel) {
                throw ValidationException::withMessages([
                    'cart' => 'Stok produk tidak ditemukan untuk salah satu item transaksi.',
                ]);
            }

            if ((int) $stockLevel->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => 'Stok ' . ($stockLevel->product?->product_name ?? 'produk') . ' tidak cukup. Sisa stok: ' . $stockLevel->quantity . '.',
                ]);
            }

            $oldStock = (int) $stockLevel->quantity;
            $stockLevel->quantity = (int) $stockLevel->quantity - $quantity;
            $stockLevel->save();

            // Record stock log for sales
            \App\Models\StockLog::create([
                'store_id'        => $storeId,
                'product_id'      => $productId,
                'user_id'         => auth()->user()?->user_id,
                'type'            => 'sale',
                'old_quantity'    => $oldStock,
                'new_quantity'    => $stockLevel->quantity,
                'quantity_change' => -$quantity,
                'notes'           => 'Pengurangan stok penjualan',
            ]);
        }
    }

    /**
     * Kembalikan stok produk sesuai item transaksi.
     */
    private function incrementStockLevels(int $storeId, array $items): void
    {
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $stockLevel = StockLevel::query()
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if ($stockLevel) {
                $oldStock = (int) $stockLevel->quantity;
                $stockLevel->quantity = (int) $stockLevel->quantity + $quantity;
                $stockLevel->save();

                // Record stock log for restoration
                \App\Models\StockLog::create([
                    'store_id'        => $storeId,
                    'product_id'      => $productId,
                    'user_id'         => auth()->user()?->user_id,
                    'type'            => 'adjustment',
                    'old_quantity'    => $oldStock,
                    'new_quantity'    => $stockLevel->quantity,
                    'quantity_change' => $quantity,
                    'notes'           => 'Pengembalian stok dari pembatalan waiting payment',
                ]);
            }
        }
    }

    // Existing methods (pendingList, payPending, dll) tetap sama...
    public function pendingList(Request $request)
    {
        abort_unless($request->user()?->role === 'cashier', 403);

        $storeId = $request->user()?->store_id;

        $rows = Transaction::query()
            ->where('store_id', $storeId)
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
                    'customer_name' => $trx->customer_name ?? 'Pelanggan Umum',
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
            'customer_name' => ['nullable', 'string', 'max:100'],
        ]);

        $total = (float) $transaction->total_amount;
        $payment = (float) $payload['payment'];

        if ($payload['payment_method'] !== 'qris' && $payment < $total) {
            return response()->json(['message' => 'Nominal pembayaran kurang dari total belanja.'], 422);
        }

        DB::transaction(function () use ($transaction, $payload, $request): void {
            $transaction->update([
                'status' => 'completed',
                'payment_method' => $payload['payment_method'],
                'customer_name' => $payload['customer_name'] ?? $transaction->customer_name ?? 'Pelanggan Umum',
                'notes' => trim(($transaction->notes ?? '') . ' | Dibayar [' . $request->user()->name . ']: ' . now()->format('Y-m-d H:i:s')),
            ]);
        });

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
            $items = $transaction->items()->get()->map(function (TransactionItem $item): array {
                return [
                    'product_id' => (int) $item->product_id,
                    'quantity' => (int) $item->quantity,
                ];
            })->all();

            $this->incrementStockLevels((int) $transaction->store_id, $items);

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
            'store',
        ]);

        return view('kasir.receipt', [
            'transaction' => $transaction,
        ]);
    }

    public function transactionHistory(Request $request)
    {
        abort_unless(in_array($request->user()?->role, ['cashier', 'admin', 'pelayan'], true), 403);

        $storeId = $request->user()?->store_id;

        $query = Transaction::query()
            ->with(['user:user_id,name'])
            ->where('store_id', $storeId)
            ->where('status', 'completed');

        if ($request->user()?->role === 'pelayan') {
            $query->where('user_id', $request->user()->user_id);
        }

        $transactions = $query
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($txn) {
                return [
                    'transaction_id' => $txn->transaction_id,
                    'id' => $txn->transaction_id,
                    'transaction_code' => $txn->transaction_code,
                    'total_amount' => $txn->total_amount,
                    'payment_method' => $txn->payment_method,
                    'status' => $txn->status,
                    'created_at' => $txn->created_at?->toIso8601String(),
                    'served_by' => $txn->user?->name ?? '-',
                    'customer_name' => $txn->customer_name ?? 'Pelanggan Umum',
                    'notes' => $txn->notes,
                    'receipt_url' => route('kasir.receipt', $txn->transaction_id),
                ];
            });

        return response()->json([
            'status' => 'success',
            'transactions' => $transactions,
        ]);
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity'   => 'required|integer|min:0',
        ]);

        $storeId   = $request->user()->store_id;
        $productId = $request->input('product_id');
        $quantity  = $request->input('quantity');

        // Get product to check unit price for stock subtotal calculation
        $product = Product::findOrFail($productId);
        $unitPrice = $product->selling_price;

        // Fetch old quantity before updating
        $oldStock = StockLevel::where('store_id', $storeId)->where('product_id', $productId)->value('quantity') ?? 0;

        $stockLevel = StockLevel::updateOrCreate(
            ['store_id' => $storeId, 'product_id' => $productId],
            [
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
                'discount'   => 0
            ]
        );

        // Record stock log for manual adjustment
        \App\Models\StockLog::create([
            'store_id'        => $storeId,
            'product_id'      => $productId,
            'user_id'         => $request->user()?->user_id,
            'type'            => 'adjustment',
            'old_quantity'    => $oldStock,
            'new_quantity'    => $quantity,
            'quantity_change' => $quantity - $oldStock,
            'notes'           => 'Penyesuaian stok manual oleh ' . ($request->user()?->name ?? 'System'),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Stok ' . $product->product_name . ' berhasil diperbarui menjadi ' . $quantity,
            'quantity' => $stockLevel->quantity
        ]);
    }

    public function convertStock(Request $request)
    {
        $request->validate([
            'source_product_id' => 'required|exists:products,product_id',
            'target_product_id' => 'required|exists:products,product_id|different:source_product_id',
            'source_quantity'   => 'required|integer|min:1',
            'ratio'             => 'required|integer|min:1',
        ]);

        $storeId   = $request->user()->store_id;
        $sourceId  = $request->input('source_product_id');
        $targetId  = $request->input('target_product_id');
        $sourceQty = $request->input('source_quantity');
        $ratio     = $request->input('ratio');

        $targetAddQty = $sourceQty * $ratio;

        return DB::transaction(function () use ($storeId, $sourceId, $targetId, $sourceQty, $targetAddQty, $request) {
            // 1. Check source stock
            $sourceStock = StockLevel::where('store_id', $storeId)
                ->where('product_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if (!$sourceStock || $sourceStock->quantity < $sourceQty) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Stok produk asal tidak mencukupi untuk dikonversi.'
                ], 422);
            }

            $oldSourceQty = $sourceStock->quantity;

            // 2. Deduct source stock
            $sourceStock->quantity -= $sourceQty;
            $sourceStock->save();

            // Record source stock log (conversion deduction)
            \App\Models\StockLog::create([
                'store_id'        => $storeId,
                'product_id'      => $sourceId,
                'user_id'         => $request->user()?->user_id,
                'type'            => 'conversion_out',
                'old_quantity'    => $oldSourceQty,
                'new_quantity'    => $sourceStock->quantity,
                'quantity_change' => -$sourceQty,
                'notes'           => 'Pemecahan/konversi stok (dikurangi) oleh ' . ($request->user()?->name ?? 'System'),
            ]);

            // 3. Add to target stock
            $targetProduct = Product::findOrFail($targetId);
            $targetStock = StockLevel::where('store_id', $storeId)
                ->where('product_id', $targetId)
                ->lockForUpdate()
                ->first();

            $oldTargetQty = $targetStock ? $targetStock->quantity : 0;

            if ($targetStock) {
                $targetStock->quantity += $targetAddQty;
                $targetStock->save();
            } else {
                $targetStock = StockLevel::create([
                    'store_id'   => $storeId,
                    'product_id' => $targetId,
                    'quantity'   => $targetAddQty,
                    'unit_price' => $targetProduct->selling_price,
                    'discount'   => 0
                ]);
            }

            // Record target stock log (conversion addition)
            \App\Models\StockLog::create([
                'store_id'        => $storeId,
                'product_id'      => $targetId,
                'user_id'         => $request->user()?->user_id,
                'type'            => 'conversion_in',
                'old_quantity'    => $oldTargetQty,
                'new_quantity'    => $targetStock->quantity,
                'quantity_change' => $targetAddQty,
                'notes'           => 'Pemecahan/konversi stok (ditambah) oleh ' . ($request->user()?->name ?? 'System'),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Konversi berhasil. Stok asal berkurang ' . $sourceQty . ', stok tujuan bertambah ' . $targetAddQty
            ]);
        });
    }

    public function getCategories(Request $request)
    {
        $categories = \App\Models\Category::all(['category_id', 'category_name']);
        return response()->json($categories);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'category_id'  => ['nullable', 'exists:categories,category_id'],
            'variant'      => ['nullable', 'string', 'max:100'],
            'unit'         => ['nullable', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
            'image'         => ['nullable', 'image', 'max:2048'],
        ]);

        $storeId = $request->user()->store_id;
        if (!$storeId) {
            return response()->json(['message' => 'User tidak terasosiasi dengan Store mana pun.'], 422);
        }

        $fileUrl = null;
        if ($request->hasFile('image')) {
            $fileUrl = $request->file('image')->store('products', 'public');
        }

        $product = DB::transaction(function () use ($request, $storeId, $fileUrl) {
            $product = Product::create([
                'category_id'    => $request->input('category_id'),
                'product_name'  => $request->input('product_name'),
                'variant'       => $request->input('variant') ?: '-',
                'unit'          => $request->input('unit') ?: '-',
                'purchase_price'=> 0,
                'selling_price' => $request->input('selling_price'),
                'file_url'      => $fileUrl,
            ]);

            StockLevel::updateOrCreate([
                'store_id'   => $storeId,
                'product_id' => $product->product_id,
            ], [
                'quantity'   => $request->input('initial_stock') ?? 0,
                'unit_price' => $request->input('selling_price'),
                'discount'   => 0,
            ]);

            // Record stock log for initial stock creation
            \App\Models\StockLog::create([
                'store_id'        => $storeId,
                'product_id'      => $product->product_id,
                'user_id'         => $request->user()->user_id,
                'type'            => 'adjustment',
                'old_quantity'    => 0,
                'new_quantity'    => $request->input('initial_stock') ?? 0,
                'quantity_change' => $request->input('initial_stock') ?? 0,
                'notes'           => 'Stok awal produk baru yang ditambahkan dari Kasir',
            ]);

            return $product;
        });

        return response()->json([
            'message' => 'Produk baru berhasil ditambahkan.',
            'product' => $product,
        ]);
    }
}
