<?php

namespace Database\Seeders;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscountSeeder extends Seeder
{
    /**
     * Create the Acme Fashion discount codes (spec 07 §3.11).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

            $discounts = [
                [
                    'code' => 'WELCOME10',
                    'value_type' => DiscountValueType::Percent,
                    'value_amount' => 10,
                    'starts_at' => '2025-01-01 00:00:00',
                    'ends_at' => '2027-12-31 23:59:59',
                    'usage_limit' => null,
                    'usage_count' => 3,
                    'rules_json' => ['min_purchase_amount' => 2000],
                    'status' => DiscountStatus::Active,
                ],
                [
                    'code' => 'FLAT5',
                    'value_type' => DiscountValueType::Fixed,
                    'value_amount' => 500,
                    'starts_at' => '2025-01-01 00:00:00',
                    'ends_at' => '2027-12-31 23:59:59',
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'rules_json' => [],
                    'status' => DiscountStatus::Active,
                ],
                [
                    'code' => 'FREESHIP',
                    'value_type' => DiscountValueType::FreeShipping,
                    'value_amount' => 0,
                    'starts_at' => '2025-01-01 00:00:00',
                    'ends_at' => '2027-12-31 23:59:59',
                    'usage_limit' => null,
                    'usage_count' => 1,
                    'rules_json' => [],
                    'status' => DiscountStatus::Active,
                ],
                [
                    'code' => 'EXPIRED20',
                    'value_type' => DiscountValueType::Percent,
                    'value_amount' => 20,
                    'starts_at' => '2024-01-01 00:00:00',
                    'ends_at' => '2024-12-31 23:59:59',
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'rules_json' => [],
                    'status' => DiscountStatus::Expired,
                ],
                [
                    'code' => 'MAXED',
                    'value_type' => DiscountValueType::Percent,
                    'value_amount' => 10,
                    'starts_at' => '2025-01-01 00:00:00',
                    'ends_at' => '2027-12-31 23:59:59',
                    'usage_limit' => 5,
                    'usage_count' => 5,
                    'rules_json' => [],
                    'status' => DiscountStatus::Active,
                ],
            ];

            foreach ($discounts as $attributes) {
                Discount::query()->updateOrCreate(
                    ['store_id' => $fashion->id, 'code' => $attributes['code']],
                    [
                        'type' => DiscountType::Code,
                        'value_type' => $attributes['value_type'],
                        'value_amount' => $attributes['value_amount'],
                        'starts_at' => $attributes['starts_at'],
                        'ends_at' => $attributes['ends_at'],
                        'usage_limit' => $attributes['usage_limit'],
                        'usage_count' => $attributes['usage_count'],
                        'rules_json' => $attributes['rules_json'],
                        'status' => $attributes['status'],
                    ],
                );
            }
        });
    }
}
