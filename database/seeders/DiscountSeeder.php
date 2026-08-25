<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscountSeeder extends Seeder
{
    /**
     * Create discount codes for the Acme Fashion store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::where('handle', 'acme-fashion')->firstOrFail();

            $discounts = [
                [
                    'code' => 'WELCOME10',
                    'value_type' => 'percent',
                    'value_amount' => 10,
                    'starts_at' => '2025-01-01',
                    'ends_at' => '2027-12-31',
                    'usage_limit' => null,
                    'usage_count' => 3,
                    'rules_json' => ['min_purchase_amount' => 2000],
                    'status' => 'active',
                ],
                [
                    'code' => 'FLAT5',
                    'value_type' => 'fixed',
                    'value_amount' => 500,
                    'starts_at' => '2025-01-01',
                    'ends_at' => '2027-12-31',
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'rules_json' => [],
                    'status' => 'active',
                ],
                [
                    'code' => 'FREESHIP',
                    'value_type' => 'free_shipping',
                    'value_amount' => 0,
                    'starts_at' => '2025-01-01',
                    'ends_at' => '2027-12-31',
                    'usage_limit' => null,
                    'usage_count' => 1,
                    'rules_json' => [],
                    'status' => 'active',
                ],
                [
                    'code' => 'EXPIRED20',
                    'value_type' => 'percent',
                    'value_amount' => 20,
                    'starts_at' => '2024-01-01',
                    'ends_at' => '2024-12-31',
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'rules_json' => [],
                    'status' => 'expired',
                ],
                [
                    'code' => 'MAXED',
                    'value_type' => 'percent',
                    'value_amount' => 10,
                    'starts_at' => '2025-01-01',
                    'ends_at' => '2027-12-31',
                    'usage_limit' => 5,
                    'usage_count' => 5,
                    'rules_json' => [],
                    'status' => 'active',
                ],
            ];

            foreach ($discounts as $discount) {
                Discount::updateOrCreate(
                    ['store_id' => $store->id, 'code' => $discount['code']],
                    [
                        'type' => 'code',
                        'value_type' => $discount['value_type'],
                        'value_amount' => $discount['value_amount'],
                        'starts_at' => $discount['starts_at'],
                        'ends_at' => $discount['ends_at'],
                        'usage_limit' => $discount['usage_limit'],
                        'usage_count' => $discount['usage_count'],
                        'rules_json' => $discount['rules_json'],
                        'status' => $discount['status'],
                    ],
                );
            }
        });
    }
}
