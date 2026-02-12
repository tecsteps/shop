<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

        $discounts = [
            [
                'code' => 'WELCOME10',
                'title' => '10% Welcome Discount',
                'type' => 'code',
                'value_type' => 'percent',
                'value_amount' => 10,
                'minimum_purchase_amount' => 2000,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => null,
                'times_used' => 3,
                'status' => 'active',
            ],
            [
                'code' => 'FLAT5',
                'title' => '5 EUR Off',
                'type' => 'code',
                'value_type' => 'fixed',
                'value_amount' => 500,
                'minimum_purchase_amount' => null,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => null,
                'times_used' => 0,
                'status' => 'active',
            ],
            [
                'code' => 'FREESHIP',
                'title' => 'Free Shipping',
                'type' => 'code',
                'value_type' => 'free_shipping',
                'value_amount' => 0,
                'minimum_purchase_amount' => null,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => null,
                'times_used' => 1,
                'status' => 'active',
            ],
            [
                'code' => 'EXPIRED20',
                'title' => '20% Off (Expired)',
                'type' => 'code',
                'value_type' => 'percent',
                'value_amount' => 20,
                'minimum_purchase_amount' => null,
                'starts_at' => '2024-01-01',
                'ends_at' => '2024-12-31',
                'usage_limit' => null,
                'times_used' => 0,
                'status' => 'expired',
            ],
            [
                'code' => 'MAXED',
                'title' => '10% Off (Maxed Out)',
                'type' => 'code',
                'value_type' => 'percent',
                'value_amount' => 10,
                'minimum_purchase_amount' => null,
                'starts_at' => '2025-01-01',
                'ends_at' => '2027-12-31',
                'usage_limit' => 5,
                'times_used' => 5,
                'status' => 'active',
            ],
        ];

        foreach ($discounts as $data) {
            Discount::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $fashion->id, 'code' => $data['code']],
                $data,
            );
        }
    }
}
