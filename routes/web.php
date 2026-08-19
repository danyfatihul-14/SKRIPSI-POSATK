<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\PublicController;

Route::get('/', [PublicController::class, 'index'])->name('public.dashboard');
Route::get('/catalog', [PublicController::class, 'catalog'])->name('public.catalog');
Route::get('/catalog/{product}', [PublicController::class, 'show'])->name('public.product.show');

Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return "Semua cache Laravel berhasil dibersihkan!";
});

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::delete('/admin/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/cashier', [KasirController::class, 'index'])->name('cashier');
    Route::get('/kasir', fn() => redirect()->route('cashier'))->name('kasir.index');
    Route::get('/kasir/search', [KasirController::class, 'search'])->name('kasir.search');
    Route::post('/kasir/order', [KasirController::class, 'storeOrder'])->name('kasir.order.store');
    Route::post('/kasir/checkout', [KasirController::class, 'checkout'])->name('kasir.checkout');
    Route::get('/kasir/pending', [KasirController::class, 'pendingList'])->name('kasir.pending.list');
    Route::post('/kasir/pending/{transaction}/pay', [KasirController::class, 'payPending'])->name('kasir.pending.pay');
    Route::delete('/kasir/pending/{transaction}', [KasirController::class, 'deletePending'])->name('kasir.pending.delete');
    Route::post('/kasir/generate-qris', [KasirController::class, 'generateQris'])->name('kasir.generate-qris');
    Route::post('/kasir/midtrans-notification', [KasirController::class, 'midtransNotification'])->name('kasir.midtrans-notification')->withoutMiddleware(['auth:sanctum']);
    Route::get('/kasir/receipt/{transaction}', [KasirController::class, 'receipt'])->name('kasir.receipt');
    Route::get('/kasir/transaction-history', [KasirController::class, 'transactionHistory'])->name('kasir.transaction-history');
    Route::get('/kasir/closing-report', [KasirController::class, 'closingReport'])->name('kasir.closing-report');
    Route::get('/kasir/closing-receipt', [KasirController::class, 'closingReceipt'])->name('kasir.closing-receipt');
    Route::post('/kasir/stok/adjust', [KasirController::class, 'adjustStock'])->name('kasir.stok.adjust');
    Route::post('/kasir/stok/convert', [KasirController::class, 'convertStock'])->name('kasir.stok.convert');
    Route::get('/kasir/categories', [KasirController::class, 'getCategories'])->name('kasir.categories');
    Route::post('/kasir/products', [KasirController::class, 'storeProduct'])->name('kasir.products.store');
    Route::post('/kasir/products/{product}/update-image', [KasirController::class, 'updateProductImage'])->name('kasir.products.update-image');
    Route::get('/cashier', function () {
        return view('kasir.dashboard');})->name('cashier');
});

require __DIR__ . '/predictions.php';
