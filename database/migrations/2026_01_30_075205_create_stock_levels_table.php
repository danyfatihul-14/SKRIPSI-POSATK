<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id('stock_level_id');

            $table->foreignId('store_id')->constrained('stores', 'store_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();

            $table->unique(['store_id', 'product_id'], 'uq_stock_levels_store_product');

            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
