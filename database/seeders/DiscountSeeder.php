<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');

        if (! $fashion) {
            return;
        }

        $discounts = [
            [
                'code' => 'WELCOME10',
                'type' => 'code',
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
                'type' => 'code',
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
                'type' => 'code',
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
                'type' => 'code',
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
                'type' => 'code',
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

        foreach ($discounts as $discount) {
            Discount::query()->updateOrCreate(
                [
                    'store_id' => $fashion->id,
                    'code' => $discount['code'],
                ],
                [
                    'type' => $discount['type'],
                    'value_type' => $discount['value_type'],
                    'value_amount' => $discount['value_amount'],
                    'starts_at' => $discount['starts_at'],
                    'ends_at' => $discount['ends_at'],
                    'usage_limit' => $discount['usage_limit'],
                    'usage_count' => $discount['usage_count'],
                    'rules_json' => $discount['rules_json'],
                    'status' => $discount['status'],
                ]
            );
        }
    }
}
