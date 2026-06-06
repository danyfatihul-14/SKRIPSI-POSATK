<?php
namespace App\Observers;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Store;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Ambil semua store yang aktif
        $stores = Store::all();

        foreach ($stores as $store) {
            // Inisialisasi stok 0 untuk setiap cabang
            StockLevel::firstOrCreate([
                'store_id' => $store->store_id,
                'product_id' => $product->product_id,
            ], [
                'quantity' => 0,
                'unit_price' => $product->selling_price,
                'discount' => 0,
            ]);
        }
    }
}
