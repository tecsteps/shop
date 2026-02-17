<?php

namespace Database\Seeders;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'WELCOME10',
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => null,
            'usage_count' => 3,
            'rules_json' => ['minimum_purchase_amount' => 2000],
            'status' => DiscountStatus::Active,
        ]);

        Discount::factory()->fixed(500)->create([
            'store_id' => $store->id,
            'code' => 'FLAT5',
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ]);

        Discount::factory()->freeShipping()->create([
            'store_id' => $store->id,
            'code' => 'FREESHIP',
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => null,
            'usage_count' => 1,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ]);

        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'EXPIRED20',
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 20,
            'starts_at' => '2024-01-01',
            'ends_at' => '2024-12-31',
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Expired,
        ]);

        Discount::factory()->create([
            'store_id' => $store->id,
            'code' => 'MAXED',
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => '2025-01-01',
            'ends_at' => '2027-12-31',
            'usage_limit' => 5,
            'usage_count' => 5,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ]);
    }
}
