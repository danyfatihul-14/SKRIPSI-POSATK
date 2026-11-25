<?php
namespace App\Observers;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Store;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     *
     * CATATAN: StockLevel sengaja TIDAK dibuat otomatis untuk semua cabang.
     * Setiap cabang hanya memiliki produk jika secara eksplisit mendaftarkannya
     * (melalui menu kasir "Tambah Produk" atau Admin Filament).
     * Ini memastikan produk dari Toko A tidak bocor/muncul di Toko B dengan stok 0.
     */
    public function created(Product $product): void
    {
        // Tidak ada auto-create StockLevel untuk semua toko.
        // StockLevel hanya dibuat oleh:
        // - KasirController::storeProduct (kasir/pelayan menambahkan produk baru)
        // - CreateProduct (admin menambahkan produk via Filament)
        // - StockLevel::updateOrCreate saat admin memilih cabang tertentu
    }
}
