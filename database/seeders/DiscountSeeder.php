<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->first();

        // WELCOME10 - 10% off, min 20 EUR purchase
        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'WELCOME10',
            'type' => 'code',
            'value_type' => 'percent',
            'value_amount' => 10,
            'status' => 'active',
            'starts_at' => '2025-01-01T00:00:00+00:00',
            'ends_at' => '2027-12-31T23:59:59+00:00',
            'usage_limit' => null,
            'usage_count' => 3,
            'rules_json' => ['min_purchase_amount' => 2000],
            'minimum_purchase_amount' => 2000,
        ]);

        // FLAT5 - 5 EUR fixed
        Discount::factory()->fixed(500)->create([
            'store_id' => $store->id,
            'code' => 'FLAT5',
            'status' => 'active',
            'starts_at' => '2025-01-01T00:00:00+00:00',
            'ends_at' => '2027-12-31T23:59:59+00:00',
            'usage_limit' => null,
            'usage_count' => 0,
        ]);

        // FREESHIP - free shipping
        Discount::factory()->freeShipping()->create([
            'store_id' => $store->id,
            'code' => 'FREESHIP',
            'status' => 'active',
            'starts_at' => '2025-01-01T00:00:00+00:00',
            'ends_at' => '2027-12-31T23:59:59+00:00',
            'usage_limit' => null,
            'usage_count' => 1,
        ]);

        // EXPIRED20 - expired
        Discount::factory()->expired()->create([
            'store_id' => $store->id,
            'code' => 'EXPIRED20',
            'value_type' => 'percent',
            'value_amount' => 20,
            'starts_at' => '2024-01-01T00:00:00+00:00',
            'ends_at' => '2024-12-31T23:59:59+00:00',
            'usage_count' => 0,
        ]);

        // MAXED - usage limit reached
        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'MAXED',
            'type' => 'code',
            'value_type' => 'percent',
            'value_amount' => 10,
            'status' => 'active',
            'starts_at' => '2025-01-01T00:00:00+00:00',
            'ends_at' => '2027-12-31T23:59:59+00:00',
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);
    }
}
