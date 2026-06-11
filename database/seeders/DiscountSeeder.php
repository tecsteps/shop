<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Seed the demo discount codes for Acme Fashion (spec 07 section 3.11).
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        $discounts = [
            [
                'code' => 'WELCOME10',
                'value_type' => 'percent',
                'value_amount' => 10,
                'starts_at' => '2025-01-01 00:00:00',
                'ends_at' => '2027-12-31 23:59:59',
                'usage_limit' => null,
                'usage_count' => 3,
                'rules_json' => ['min_purchase_amount' => 2000],
                'status' => 'active',
            ],
            [
                'code' => 'FLAT5',
                'value_type' => 'fixed',
                'value_amount' => 500,
                'starts_at' => '2025-01-01 00:00:00',
                'ends_at' => '2027-12-31 23:59:59',
                'usage_limit' => null,
                'usage_count' => 0,
                'rules_json' => [],
                'status' => 'active',
            ],
            [
                'code' => 'FREESHIP',
                'value_type' => 'free_shipping',
                'value_amount' => 0,
                'starts_at' => '2025-01-01 00:00:00',
                'ends_at' => '2027-12-31 23:59:59',
                'usage_limit' => null,
                'usage_count' => 1,
                'rules_json' => [],
                'status' => 'active',
            ],
            [
                'code' => 'EXPIRED20',
                'value_type' => 'percent',
                'value_amount' => 20,
                'starts_at' => '2024-01-01 00:00:00',
                'ends_at' => '2024-12-31 23:59:59',
                'usage_limit' => null,
                'usage_count' => 0,
                'rules_json' => [],
                'status' => 'expired',
            ],
            [
                'code' => 'MAXED',
                'value_type' => 'percent',
                'value_amount' => 10,
                'starts_at' => '2025-01-01 00:00:00',
                'ends_at' => '2027-12-31 23:59:59',
                'usage_limit' => 5,
                'usage_count' => 5,
                'rules_json' => [],
                'status' => 'active',
            ],
        ];

        foreach ($discounts as $attributes) {
            Discount::query()->withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey(), 'code' => $attributes['code']],
                [...$attributes, 'type' => 'code'],
            );
        }
    }
}
