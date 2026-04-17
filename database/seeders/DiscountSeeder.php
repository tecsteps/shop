<?php

namespace Database\Seeders;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        Discount::create([
            'store_id' => $store->id,
            'type' => DiscountType::Code,
            'code' => 'WELCOME10',
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => ['min_purchase_amount' => 2000],
            'status' => DiscountStatus::Active,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'type' => DiscountType::Code,
            'code' => 'FLAT5',
            'value_type' => DiscountValueType::Fixed,
            'value_amount' => 500,
            'starts_at' => now(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'type' => DiscountType::Code,
            'code' => 'FREESHIP',
            'value_type' => DiscountValueType::FreeShipping,
            'value_amount' => 0,
            'starts_at' => now(),
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'type' => DiscountType::Code,
            'code' => 'EXPIRED20',
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 20,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'usage_limit' => null,
            'usage_count' => 0,
            'rules_json' => [],
            'status' => DiscountStatus::Expired,
        ]);

        Discount::create([
            'store_id' => $store->id,
            'type' => DiscountType::Code,
            'code' => 'MAXED',
            'value_type' => DiscountValueType::Percent,
            'value_amount' => 10,
            'starts_at' => now()->subMonth(),
            'ends_at' => null,
            'usage_limit' => 10,
            'usage_count' => 10,
            'rules_json' => [],
            'status' => DiscountStatus::Active,
        ]);
    }
}
