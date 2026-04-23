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
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');

            $table->foreignId('category_id')->nullable()->constrained('categories', 'category_id')->nullOnDelete();

            $table->string('product_name', 150)->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_url')->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->enum('unit', ['biji', 'lusin', 'pack', 'dus', 'rim', 'pak', 'box', 'rol', 'set'])->default('biji');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
