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
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        $discounts = [
            [
                'code' => 'WELCOME10',
                'title' => 'Welcome 10% Off',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Percent,
                'value_amount' => 10,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => null,
                'usage_count' => 3,
                'minimum_purchase' => 2000,
                'status' => DiscountStatus::Active,
                'rules_json' => ['min_purchase_amount' => 2000],
            ],
            [
                'code' => 'FLAT5',
                'title' => 'Flat 5 EUR Off',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Fixed,
                'value_amount' => 500,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => null,
                'usage_count' => 0,
                'minimum_purchase' => null,
                'status' => DiscountStatus::Active,
                'rules_json' => [],
            ],
            [
                'code' => 'FREESHIP',
                'title' => 'Free Shipping',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::FreeShipping,
                'value_amount' => 0,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => null,
                'usage_count' => 1,
                'minimum_purchase' => null,
                'status' => DiscountStatus::Active,
                'rules_json' => [],
            ],
            [
                'code' => 'EXPIRED20',
                'title' => 'Expired 20% Off',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Percent,
                'value_amount' => 20,
                'starts_at' => '2024-01-01',
                'ends_at' => '2024-12-31',
                'usage_limit' => null,
                'usage_count' => 0,
                'minimum_purchase' => null,
                'status' => DiscountStatus::Expired,
                'rules_json' => [],
            ],
            [
                'code' => 'MAXED',
                'title' => 'Maxed Out 10% Off',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Percent,
                'value_amount' => 10,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => 5,
                'usage_count' => 5,
                'minimum_purchase' => null,
                'status' => DiscountStatus::Active,
                'rules_json' => [],
            ],
        ];

        foreach ($discounts as $data) {
            Discount::firstOrCreate(
                ['store_id' => $store->id, 'code' => $data['code']],
                $data
            );
        }
    }
}
