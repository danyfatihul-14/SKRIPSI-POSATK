<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class KasirSalesByHourTest extends TestCase
{
    public function test_daily_sales_by_hour_endpoint_returns_json(): void
    {
        $user = User::factory()->make([
            'user_id' => 1,
            'store_id' => 1,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/kasir/report-sales-by-hour?date=' . date('Y-m-d') . '&hour=21');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'date',
                'store_id',
                'selected_hour',
                'total_unit',
                'hourly',
                'products',
            ]);
    }
}
