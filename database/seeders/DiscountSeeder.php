<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        if (! $store) {
            return;
        }

        // 10% welcome discount
        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'WELCOME10',
            'type' => 'code',
            'value_type' => 'percent',
            'value_amount' => 10,
            'status' => 'active',
            'starts_at' => now()->subMonth()->toIso8601String(),
            'ends_at' => now()->addYear()->toIso8601String(),
        ]);

        // Flat 5 EUR off
        Discount::factory()->fixed(500)->create([
            'store_id' => $store->id,
            'code' => 'FLAT5',
        ]);

        // Free shipping
        Discount::factory()->freeShipping()->create([
            'store_id' => $store->id,
            'code' => 'FREESHIP',
        ]);

        // Expired discount
        Discount::factory()->expired()->create([
            'store_id' => $store->id,
            'code' => 'EXPIRED20',
            'value_amount' => 20,
        ]);

        // Maxed out usage
        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'MAXED',
            'value_type' => 'percent',
            'value_amount' => 15,
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);
    }
}
