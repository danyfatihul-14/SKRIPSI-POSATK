<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id('store_id');

            $table->string('code_store', 20)->unique();
            $table->string('name_store', 255);
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('manager_name', 100)->nullable();
            $table->tinyInteger('is_active')->default(1);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};