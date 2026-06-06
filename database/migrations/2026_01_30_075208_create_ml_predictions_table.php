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
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id('ml_id');

            $table->foreignId('store_id')->constrained('stores', 'store_id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'product_id')->onDelete('cascade');
            
            $table->date('prediction_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('selling_price');
            $table->decimal('predicted_revenue', 12, 2);
            $table->integer('predicted_quantity')->nullable();
            $table->decimal('confidence_level', 5, 4);
            $table->json('featured_used')->nullable();
            $table->string('model_version', 50);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
    }
};
