<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        return view('kasir.dashboard');
    }

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));
        $storeId = $request->user()?->store_id;

        $query = Product::query()->with([
            'stockLevels' => fn($subQuery) => $storeId ? $subQuery->where('store_id', $storeId) : $subQuery,
            'category',
        ]);

        if ($storeId) {
            $query->whereHas('stockLevels', function ($subQuery) use ($storeId) {
                $subQuery->where('store_id', $storeId);
            });
        }

        if (! empty($q)) {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('product_name', 'like', "%$q%")
                    ->orWhere('variant', 'like', "%$q%")
                    ->orWhereHas('category', fn($catQuery) => $catQuery->where('category_name', 'like', "%$q%"));
            });
        }

        $dynamicCategories = \App\Models\Category::where('is_dynamic_price', true)->get();

        $products = $query->get()->map(function ($product) use ($dynamicCategories, $storeId) {
            $stockLevel = $storeId
                ? $product->stockLevels->firstWhere('store_id', $storeId)
                : $product->stockLevels->first();

            // Check if dynamic price
            $isDynamic = false;
            $catId = $product->category_id;
            $prodName = $product->product_name;
            $catName = $product->category?->category_name ?? '';

            foreach ($dynamicCategories as $cat) {
                if ($catId == $cat->category_id) {
                    $isDynamic = true;
                    break;
                }
                if (!empty($cat->keywords)) {
                    $keywordsList = is_array($cat->keywords) ? $cat->keywords : json_decode($cat->keywords, true);
                    if (!is_array($keywordsList)) {
                        $keywordsList = array_map('trim', explode(',', $cat->keywords));
                    }
                    foreach ($keywordsList as $kw) {
                        if ($kw !== '' && (stripos($prodName, $kw) !== false || stripos($catName, $kw) !== false)) {
                            $isDynamic = true;
                            break 2;
                        }
                    }
                }
            }

            return [
                'product_id'    => $product->product_id,
                'product_name'  => $product->product_name,
                'category_name' => $product->category?->category_name ?? '',
                'variant'       => $product->variant ?? '-',
                'unit'          => $product->unit ?? '-',
                'selling_price' => $product->selling_price ?? 0,
                'stock'         => (int) ($stockLevel?->quantity ?? 0),
                'image_url'     => $product->file_url ? asset('storage/' . $product->file_url) : null,
                'is_dynamic_price' => $isDynamic,
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

            $userId = $request->user()?->user_id ?? 0;
            $lockKey = 'lock_store_order_' . $userId . '_' . md5(json_encode($cart));
            if (! Cache::add($lockKey, true, 4)) {
                return response()->json(['message' => 'Pesanan sedang diproses, harap tunggu sejenak.'], 429);
            }

            $storeId = (int) ($request->user()?->store_id ?? 0);
            $items = $this->buildCartItems($cart, $storeId);
            $subtotal = (float) collect($items)->sum('subtotal');

            DB::transaction(function () use ($request, $items, $subtotal) {
                $transaction = Transaction::create([
                    'store_id'       => $request->user()->store_id,
                    'user_id'        => $request->user()->user_id,
                    'waiter_id'      => $request->user()->role === 'pelayan' ? $request->user()->user_id : null,
                    'cashier_id'     => in_array($request->user()->role, ['cashier', 'owner', 'admin']) ? $request->user()->user_id : null,
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
        abort_unless(in_array($request->user()?->role, ['cashier', 'pelayan'], true), 403);

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
            $transactionId = $request->input('transaction_id');
            $skipStockCheck = false;
            if ($transactionId) {
                $skipStockCheck = Transaction::query()
                    ->where('transaction_id', $transactionId)
                    ->where('status', 'pending_payment')
                    ->exists();
            }

            $storeId = (int) ($request->user()?->store_id ?? 0);
            $items = $this->buildCartItems($cart, $storeId, $skipStockCheck);

            $itemDetails = [];
            $calcSubtotal = 0;
            foreach ($items as $item) {
                $itemSubtotal = (int) round($item['subtotal']);
                $calcSubtotal += $itemSubtotal;
                $pName = mb_substr($item['product_name'] . ($item['variant'] && $item['variant'] !== '-' ? " ({$item['variant']})" : ''), 0, 50);
                $itemDetails[] = [
                    'id'       => (string) $item['product_id'],
                    'price'    => $itemSubtotal,
                    'quantity' => 1,
                    'name'     => $pName,
                ];
            }

            $qrisFee = (int) round($calcSubtotal * 0.007);
            $grossAmount = $calcSubtotal + $qrisFee;

            if ($grossAmount < 1000) {
                throw ValidationException::withMessages([
                    'total_amount' => 'Minimal pembayaran QRIS Rp 1.000.',
                ]);
            }

            if ($qrisFee > 0) {
                $itemDetails[] = [
                    'id'       => 'FEE-QRIS',
                    'price'    => $qrisFee,
                    'quantity' => 1,
                    'name'     => 'Biaya Layanan QRIS 0.7%',
                ];
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

            $params = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails,
                'item_details' => $itemDetails,
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
        abort_unless(in_array($request->user()?->role, ['cashier', 'pelayan'], true), 403);

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

        $userId = $request->user()?->user_id ?? 0;
        $lockKey = 'lock_checkout_' . $userId . '_' . md5($payload['cart']);
        if (! Cache::add($lockKey, true, 4)) {
            return response()->json(['message' => 'Transaksi sedang diproses, harap tunggu sejenak.'], 429);
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

        $cashPaid = $payload['payment_method'] === 'qris' ? $subtotal : $payment;
        $cashChange = $payload['payment_method'] === 'qris' ? 0.00 : max(0.00, $payment - $subtotal);

        $transaction = DB::transaction(function () use ($request, $items, $subtotal, $payload, $cashPaid, $cashChange) {
            $transaction = Transaction::create([
                'store_id'       => $request->user()->store_id,
                'user_id'        => $request->user()->user_id,
                'waiter_id'      => $request->user()->role === 'pelayan' ? $request->user()->user_id : null,
                'cashier_id'     => in_array($request->user()->role, ['cashier', 'owner', 'admin']) ? $request->user()->user_id : null,
                'subtotal'       => $subtotal,
                'tax'            => 0,
                'discount'       => 0,
                'total_amount'   => $subtotal,
                'status'         => 'completed',
                'payment_method' => $payload['payment_method'],
                'cash_paid'      => $cashPaid,
                'cash_change'    => $cashChange,
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

    private function buildCartItems(array $rawCart, ?int $storeId = null, bool $skipStockCheck = false): array
    {
        $normalized = collect($rawCart)
            ->map(function ($row) {
                return [
                    'product_id' => (int) ($row['product_id'] ?? $row['id'] ?? 0),
                    'price'      => (float) ($row['price'] ?? 0),
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
                'category',
            ])
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        $items = $normalized->map(function ($row) use ($products, $skipStockCheck) {
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

            // Check if it is a dynamic price product (DB-driven)
            $isDynamicPrice = false;
            $catId = $product->category_id;
            $prodName = $product->product_name;
            $catName = $product->category?->category_name ?? '';

            $dynamicCategories = \App\Models\Category::where('is_dynamic_price', true)->get();
            foreach ($dynamicCategories as $cat) {
                if ($catId == $cat->category_id) {
                    $isDynamicPrice = true;
                    break;
                }
                if (!empty($cat->keywords)) {
                    $keywordsList = is_array($cat->keywords) ? $cat->keywords : json_decode($cat->keywords, true);
                    if (!is_array($keywordsList)) {
                        $keywordsList = array_map('trim', explode(',', $cat->keywords));
                    }
                    foreach ($keywordsList as $kw) {
                        if ($kw !== '' && (stripos($prodName, $kw) !== false || stripos($catName, $kw) !== false)) {
                            $isDynamicPrice = true;
                            break 2;
                        }
                    }
                }
            }

            if (! $skipStockCheck && ! $isDynamicPrice && $qty > $stock) {
                throw ValidationException::withMessages([
                    'cart' => "Stok {$product->product_name} tidak cukup. Sisa stok: {$stock}.",
                ]);
            }

            if (isset($row['price']) && (float)$row['price'] > 0) {
                $unitPrice = (float) $row['price'];
            } else {
                $unitPrice = (float) ($product->selling_price ?? 0);
            }

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
        $dynamicCategories = \App\Models\Category::where('is_dynamic_price', true)->get();

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $product = Product::query()->with('category')->find($productId);

            // Cek apakah produk ini memiliki harga / stok dinamis
            $isDynamicPrice = false;
            if ($product) {
                $isDynamicPrice = (bool) ($product->category?->is_dynamic_price ?? false);
                if (! $isDynamicPrice) {
                    $catId = $product->category_id;
                    $prodName = $product->product_name;
                    $catName = $product->category?->category_name ?? '';

                    foreach ($dynamicCategories as $cat) {
                        if ($catId == $cat->category_id) {
                            $isDynamicPrice = true;
                            break;
                        }
                        if (! empty($cat->keywords)) {
                            $keywordsList = is_array($cat->keywords) ? $cat->keywords : json_decode($cat->keywords, true);
                            if (! is_array($keywordsList)) {
                                $keywordsList = array_map('trim', explode(',', $cat->keywords));
                            }
                            foreach ($keywordsList as $kw) {
                                if ($kw !== '' && (stripos($prodName, $kw) !== false || stripos($catName, $kw) !== false)) {
                                    $isDynamicPrice = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            $stockLevel = StockLevel::query()
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stockLevel) {
                if ($isDynamicPrice) {
                    $stockLevel = StockLevel::create([
                        'store_id'   => $storeId,
                        'product_id' => $productId,
                        'quantity'   => 0,
                        'unit_price' => (float) ($item['unit_price'] ?? 0),
                    ]);
                } else {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok produk tidak ditemukan untuk salah satu item transaksi.',
                    ]);
                }
            }

            if (! $isDynamicPrice && (int) $stockLevel->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => 'Stok ' . ($stockLevel->product?->product_name ?? 'produk') . ' tidak cukup. Sisa stok: ' . $stockLevel->quantity . '.',
                ]);
            }

            $oldStock = (int) $stockLevel->quantity;
            $stockLevel->quantity = max(0, (int) $stockLevel->quantity - $quantity);
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
                'notes'           => 'Pengurangan stok penjualan' . ($isDynamicPrice ? ' (Stok Dinamis)' : ''),
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
        abort_unless(in_array($request->user()?->role, ['cashier', 'pelayan'], true), 403);

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
        abort_unless(in_array($request->user()?->role, ['cashier', 'pelayan'], true), 403);

        if ($transaction->status !== 'pending_payment') {
            return response()->json(['message' => 'Transaksi bukan pending payment.'], 422);
        }

        $lockKey = 'lock_pay_pending_' . $transaction->transaction_id;
        if (! Cache::add($lockKey, true, 4)) {
            return response()->json(['message' => 'Pembayaran transaksi ini sedang diproses, harap tunggu.'], 429);
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

        $cashPaid = $payload['payment_method'] === 'qris' ? $total : $payment;
        $cashChange = $payload['payment_method'] === 'qris' ? 0.00 : max(0.00, $payment - $total);

        DB::transaction(function () use ($transaction, $payload, $request, $cashPaid, $cashChange): void {
            $transaction->update([
                'status' => 'completed',
                'payment_method' => $payload['payment_method'],
                'cash_paid' => $cashPaid,
                'cash_change' => $cashChange,
                'cashier_id' => $request->user()->user_id,
                'customer_name' => $payload['customer_name'] ?? $transaction->customer_name ?? 'Pelanggan Umum',
                'notes' => trim(($transaction->notes ?? '') . ' | Dibayar [' . $request->user()->name . ']: ' . now('Asia/Jakarta')->format('Y-m-d H:i:s')),
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
        abort_unless(in_array($request->user()?->role, ['cashier', 'pelayan'], true), 403);

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
            'user:user_id,name,role',
            'waiter:user_id,name',
            'cashier:user_id,name',
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
            ->with(['user:user_id,name,role', 'waiter:user_id,name', 'cashier:user_id,name', 'items.product:product_id,product_name'])
            ->where('store_id', $storeId)
            ->where('status', 'completed');

        if ($request->user()?->role === 'pelayan') {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->user_id)
                  ->orWhere('waiter_id', $request->user()->user_id);
            });
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
                    'served_by' => $txn->cashier_name,
                    'cashier_name' => $txn->cashier_name,
                    'waiter_name' => $txn->waiter_name,
                    'customer_name' => $txn->customer_name ?? 'Pelanggan Umum',
                    'notes' => $txn->notes,
                    'receipt_url' => route('kasir.receipt', $txn->transaction_id),
                    'items' => $txn->items->map(function ($item) {
                        return [
                            'product_name' => $item->product?->product_name ?? 'Produk',
                            'quantity' => (int) $item->quantity,
                            'unit_price' => (float) $item->unit_price,
                            'subtotal' => (float) $item->subtotal,
                        ];
                    })->values()->all(),
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

        // Record stock log for manual adjustment (incoming goods)
        \App\Models\StockLog::create([
            'store_id'        => $storeId,
            'product_id'      => $productId,
            'user_id'         => $request->user()?->user_id,
            'type'            => 'adjustment',
            'old_quantity'    => $oldStock,
            'new_quantity'    => $quantity,
            'quantity_change' => $quantity - $oldStock,
            'notes'           => 'Pencatatan barang masuk oleh ' . ($request->user()?->name ?? 'System'),
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Pencatatan barang masuk ' . $product->product_name . ' berhasil. Stok sekarang: ' . $quantity,
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

        $productName = trim($request->input('product_name'));
        $variant = trim($request->input('variant') ?: '-');
        $unit = trim($request->input('unit') ?: '-');

        // Check if exact same product_name and variant already exists (case-insensitive & trimmed)
        $existingProduct = Product::whereRaw('LOWER(TRIM(product_name)) = ?', [mb_strtolower($productName)])
            ->whereRaw('LOWER(TRIM(variant)) = ?', [mb_strtolower($variant)])
            ->first();

        if ($existingProduct) {
            $variantText = ($variant !== '' && $variant !== '-') ? " dengan variasi '{$variant}'" : "";
            return response()->json([
                'message' => "Produk '{$productName}'{$variantText} sudah ada di sistem! Silakan gunakan nama variasi lain.",
            ], 422);
        }

        $fileUrl = null;
        if ($request->hasFile('image')) {
            $fileUrl = $request->file('image')->store('products', 'public');
        } else {
            // Inherit existing variant image if available
            $existingImage = Product::whereRaw('LOWER(TRIM(product_name)) = ?', [mb_strtolower($productName)])
                ->whereNotNull('file_url')
                ->where('file_url', '!=', '')
                ->value('file_url');
            if ($existingImage) {
                $fileUrl = $existingImage;
            }
        }

        $product = DB::transaction(function () use ($request, $storeId, $productName, $variant, $unit, $fileUrl) {
            $product = Product::create([
                'category_id'    => $request->input('category_id'),
                'product_name'  => $productName,
                'variant'       => $variant,
                'unit'          => $unit,
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

        // Format product data for frontend response
        $stockLvl = StockLevel::where('store_id', $storeId)->where('product_id', $product->product_id)->first();
        $formattedProduct = [
            'product_id'    => $product->product_id,
            'product_name'  => $product->product_name,
            'variant'       => $product->variant,
            'unit'          => $product->unit,
            'selling_price' => (float) $product->selling_price,
            'file_url'      => $product->file_url,
            'image_url'     => $product->file_url ? asset('storage/' . $product->file_url) : null,
            'stock'         => (int) ($stockLvl?->quantity ?? 0),
            'discount'      => (float) ($stockLvl?->discount ?? 0),
        ];

        return response()->json([
            'message' => 'Produk baru berhasil ditambahkan.',
            'product' => $formattedProduct,
        ]);
    }

    public function updateProductImage(Request $request, Product $product)
    {
        abort_unless(in_array($request->user()?->role, ['owner', 'admin', 'cashier', 'pelayan'], true), 403);

        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'image_base64' => ['nullable', 'string'],
        ]);

        $filePath = null;

        if ($request->hasFile('image')) {
            $filePath = $request->file('image')->store('products', 'public');
        } elseif ($request->input('image_base64')) {
            $base64String = $request->input('image_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
                $data = substr($base64String, strpos($base64String, ',') + 1);
                $type = strtolower($type[1]);
                if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'webp'])) {
                    $type = 'png';
                }
                $data = base64_decode($data);
                if ($data !== false) {
                    $fileName = 'products/' . \Illuminate\Support\Str::random(40) . '.' . $type;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $data);
                    $filePath = $fileName;
                }
            }
        }

        if (!$filePath) {
            return response()->json(['message' => 'Tidak ada file gambar atau foto kamera yang dikirim.'], 422);
        }

        // Update image for this product and all its variants (same product_name)
        Product::where('product_name', $product->product_name)->update(['file_url' => $filePath]);

        return response()->json([
            'message' => 'Foto produk dan seluruh variansinya berhasil diperbarui.',
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'image_url' => asset('storage/' . $filePath),
        ]);
    }

    public function closingReport(Request $request)
    {
        abort_unless(in_array(strtolower((string) $request->user()?->role), ['cashier', 'kasir', 'admin', 'owner', 'pelayan', 'waiter'], true), 403);

        $date = $request->input('date', date('Y-m-d'));
        $storeId = $request->user()?->store_id;
        if (!$storeId) {
            $storeId = \App\Models\Store::query()->value('store_id');
        }

        $query = Transaction::query()
            ->with(['items.product:product_id,product_name,variant', 'waiter:user_id,name', 'cashier:user_id,name'])
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereDate('created_at', $date);

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $totalCount = $transactions->count();
        $totalAmount = (float) $transactions->sum('total_amount');
        
        $cashTransactions = $transactions->filter(fn($t) => strtolower($t->payment_method ?? '') === 'cash');
        $qrisTransactions = $transactions->filter(fn($t) => strtolower($t->payment_method ?? '') === 'qris');

        $totalCash = (float) $cashTransactions->sum('total_amount');
        $totalQris = (float) $qrisTransactions->sum('total_amount');

        // Summarize items sold
        $productSummaryMap = [];
        $totalQtySold = 0;

        foreach ($transactions as $t) {
            foreach ($t->items as $item) {
                $pName = $item->product?->product_name ?? 'Produk';
                $vName = trim($item->product?->variant ?? '');
                $key = $pName . ($vName !== '' && $vName !== '-' ? " ({$vName})" : '');
                
                if (!isset($productSummaryMap[$key])) {
                    $productSummaryMap[$key] = [
                        'name' => $key,
                        'qty' => 0,
                        'subtotal' => 0.0,
                    ];
                }
                $productSummaryMap[$key]['qty'] += (int) $item->quantity;
                $productSummaryMap[$key]['subtotal'] += (float) $item->subtotal;
                $totalQtySold += (int) $item->quantity;
            }
        }

        usort($productSummaryMap, fn($a, $b) => $b['qty'] <=> $a['qty']);

        return response()->json([
            'status' => 'success',
            'cashier_name' => $request->user()->name,
            'store_name' => $request->user()->store?->name_store ?? 'Toko Intan',
            'date' => $date,
            'formatted_date' => \Carbon\Carbon::parse($date)->translatedFormat('d F Y'),
            'total_transactions' => $totalCount,
            'total_amount' => $totalAmount,
            'total_cash' => $totalCash,
            'total_qris' => $totalQris,
            'total_qty_sold' => $totalQtySold,
            'products_summary' => array_values($productSummaryMap),
            'transactions' => $transactions->map(fn($t) => [
                'transaction_id' => $t->transaction_id,
                'transaction_code' => $t->transaction_code,
                'created_at' => $t->created_at?->format('H:i:s'),
                'payment_method' => strtoupper($t->payment_method ?? 'CASH'),
                'customer_name' => $t->customer_name ?? 'Pelanggan Umum',
                'cashier_name' => $t->cashier_name,
                'waiter_name' => $t->waiter_name,
                'total_amount' => (float) $t->total_amount,
                'cash_paid' => (float) ($t->cash_paid ?? $t->total_amount),
                'cash_change' => (float) ($t->cash_change ?? 0),
            ]),
        ]);
    }

    public function closingReceipt(Request $request)
    {
        abort_unless(in_array(strtolower((string) $request->user()?->role), ['cashier', 'kasir', 'admin', 'owner', 'pelayan', 'waiter'], true), 403);

        $date = $request->input('date', date('Y-m-d'));
        $storeId = $request->user()?->store_id;
        if (!$storeId) {
            $storeId = \App\Models\Store::query()->value('store_id');
        }

        $query = Transaction::query()
            ->with(['items.product:product_id,product_name,variant', 'waiter:user_id,name', 'cashier:user_id,name'])
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->whereDate('created_at', $date);

        $transactions = $query->orderBy('created_at', 'asc')->get();

        $totalCount = $transactions->count();
        $totalAmount = (float) $transactions->sum('total_amount');
        
        $cashTransactions = $transactions->filter(fn($t) => strtolower($t->payment_method ?? '') === 'cash');
        $qrisTransactions = $transactions->filter(fn($t) => strtolower($t->payment_method ?? '') === 'qris');

        $totalCash = (float) $cashTransactions->sum('total_amount');
        $totalQris = (float) $qrisTransactions->sum('total_amount');

        // Summarize items sold (Products Keluar) for restocking / kulakan
        $productSummaryMap = [];
        $totalQtySold = 0;

        foreach ($transactions as $t) {
            foreach ($t->items as $item) {
                $pName = $item->product?->product_name ?? 'Produk';
                $vName = trim($item->product?->variant ?? '');
                $key = $pName . ($vName !== '' && $vName !== '-' ? " ({$vName})" : '');
                
                if (!isset($productSummaryMap[$key])) {
                    $productSummaryMap[$key] = [
                        'name' => $key,
                        'qty' => 0,
                        'subtotal' => 0.0,
                    ];
                }
                $productSummaryMap[$key]['qty'] += (int) $item->quantity;
                $productSummaryMap[$key]['subtotal'] += (float) $item->subtotal;
                $totalQtySold += (int) $item->quantity;
            }
        }

        usort($productSummaryMap, fn($a, $b) => $b['qty'] <=> $a['qty']);

        return view('kasir.closing_receipt', [
            'cashier' => $request->user(),
            'store' => $request->user()->store,
            'date' => $date,
            'formatted_date' => \Carbon\Carbon::parse($date)->translatedFormat('d F Y'),
            'totalCount' => $totalCount,
            'totalAmount' => $totalAmount,
            'totalCash' => $totalCash,
            'totalQris' => $totalQris,
            'totalQtySold' => $totalQtySold,
            'productsSummary' => array_values($productSummaryMap),
            'transactions' => $transactions,
        ]);
    }
}
