<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();
        app()->instance('current_store', $store);

        $discounts = [
            [
                'code' => 'WELCOME10', 'type' => 'code', 'value_type' => 'percent',
                'value_amount' => 10, 'starts_at' => '2025-01-01', 'ends_at' => '2027-12-31',
                'usage_limit' => null, 'usage_count' => 3, 'status' => 'active',
                'rules_json' => ['min_purchase_amount' => 2000],
            ],
            [
                'code' => 'FLAT5', 'type' => 'code', 'value_type' => 'fixed',
                'value_amount' => 500, 'starts_at' => '2025-01-01', 'ends_at' => '2027-12-31',
                'usage_limit' => null, 'usage_count' => 0, 'status' => 'active',
                'rules_json' => [],
            ],
            [
                'code' => 'FREESHIP', 'type' => 'code', 'value_type' => 'free_shipping',
                'value_amount' => 0, 'starts_at' => '2025-01-01', 'ends_at' => '2027-12-31',
                'usage_limit' => null, 'usage_count' => 1, 'status' => 'active',
                'rules_json' => [],
            ],
            [
                'code' => 'EXPIRED20', 'type' => 'code', 'value_type' => 'percent',
                'value_amount' => 20, 'starts_at' => '2024-01-01', 'ends_at' => '2024-12-31',
                'usage_limit' => null, 'usage_count' => 0, 'status' => 'expired',
                'rules_json' => [],
            ],
            [
                'code' => 'MAXED', 'type' => 'code', 'value_type' => 'percent',
                'value_amount' => 10, 'starts_at' => '2025-01-01', 'ends_at' => '2027-12-31',
                'usage_limit' => 5, 'usage_count' => 5, 'status' => 'active',
                'rules_json' => [],
            ],
        ];

        foreach ($discounts as $data) {
            Discount::create(array_merge($data, ['store_id' => $store->id]));
        }
    }
}
