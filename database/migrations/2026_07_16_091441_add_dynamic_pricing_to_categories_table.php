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
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_dynamic_price')->default(false)->after('description');
            $table->decimal('profit_margin', 5, 2)->default(0.00)->after('is_dynamic_price');
            $table->text('keywords')->nullable()->after('profit_margin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['is_dynamic_price', 'profit_margin', 'keywords']);
        });
    }
};
