<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('waiter_id')->nullable()->after('user_id')->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->after('waiter_id')->constrained('users', 'user_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['waiter_id']);
            $table->dropForeign(['cashier_id']);
            $table->dropColumn(['waiter_id', 'cashier_id']);
        });
    }
};
