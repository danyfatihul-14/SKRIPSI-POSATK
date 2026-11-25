<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ubah tipe kolom unit dari ENUM ke VARCHAR(50) pada tabel products
        DB::statement("ALTER TABLE `products` MODIFY COLUMN `unit` VARCHAR(50) NOT NULL DEFAULT 'biji';");

        // 2. Tambahkan index pencarian cepat jika belum ada pada tabel products
        try {
            DB::statement("ALTER TABLE `products` ADD INDEX `idx_products_product_name` (`product_name`);");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `products` ADD INDEX `idx_products_variant` (`variant`);");
        } catch (\Throwable $e) {}

        // 3. Tambahkan kolom purchase_price pada tabel stock_levels untuk isolasi Harga Beli per toko
        Schema::table('stock_levels', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_levels', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->nullable()->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_levels', function (Blueprint $table) {
            if (Schema::hasColumn('stock_levels', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
        });

        try {
            DB::statement("ALTER TABLE `products` DROP INDEX `idx_products_product_name`;");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `products` DROP INDEX `idx_products_variant`;");
        } catch (\Throwable $e) {}

        DB::statement("ALTER TABLE `products` MODIFY COLUMN `unit` ENUM('biji', 'lusin', 'pack', 'dus', 'rim', 'pak', 'box', 'rol', 'set') NOT NULL DEFAULT 'biji';");
    }
};
