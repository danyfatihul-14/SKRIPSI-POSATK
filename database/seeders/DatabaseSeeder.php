<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a store first
        $store = Store::create([
            'code_store' => 'STR001',
            'name_store' => 'Main Store',
            'address' => '123 Main Street',
            'phone' => '021-1234567',
            'manager_name' => 'John Doe',
            'is_active' => 1,
        ]);

        // Create test user
        User::create([
            'store_id' => $store->store_id,
            'name' => 'Test User',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'is_active' => 1,
        ]);

        User::create([
            'store_id' => $store->store_id,
            'name' => 'Cashier',
            'username' => 'cashier',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => 1,
        ]);
    }
}
