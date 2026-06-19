<?php
// filepath: database/migrations/2026_06_18_000000_create_stock_logs_table.php

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
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id('stock_log_id');
            
            $table->foreignId('store_id')->constrained('stores', 'store_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            
            $table->string('type'); // e.g. 'adjustment', 'conversion_in', 'conversion_out', 'sale'
            $table->integer('old_quantity');
            $table->integer('new_quantity');
            $table->integer('quantity_change');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};
