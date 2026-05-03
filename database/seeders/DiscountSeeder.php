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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        foreach ([
            ['WELCOME10', DiscountValueType::Percent, 10, now()->subDay(), now()->addYear(), null, 0, DiscountStatus::Active, ['min_purchase_amount' => 1000]],
            ['FLAT5', DiscountValueType::Fixed, 500, now()->subDay(), now()->addYear(), null, 0, DiscountStatus::Active, ['min_purchase_amount' => 2000]],
            ['FREESHIP', DiscountValueType::FreeShipping, 0, now()->subDay(), now()->addYear(), null, 0, DiscountStatus::Active, ['min_purchase_amount' => null]],
            ['EXPIRED20', DiscountValueType::Percent, 20, now()->subYear(), now()->subDay(), null, 0, DiscountStatus::Expired, ['min_purchase_amount' => null]],
            ['MAXED', DiscountValueType::Percent, 10, now()->subDay(), now()->addYear(), 5, 5, DiscountStatus::Active, ['min_purchase_amount' => null]],
        ] as [$code, $valueType, $valueAmount, $startsAt, $endsAt, $usageLimit, $usageCount, $status, $rules]) {
            Discount::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'code' => $code,
                ],
                [
                    'type' => DiscountType::Code,
                    'value_type' => $valueType,
                    'value_amount' => $valueAmount,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'usage_limit' => $usageLimit,
                    'usage_count' => $usageCount,
                    'rules_json' => [
                        'min_purchase_amount' => $rules['min_purchase_amount'],
                        'applicable_product_ids' => null,
                        'applicable_collection_ids' => null,
                        'customer_eligibility' => 'all',
                    ],
                    'status' => $status,
                ],
            );
        }
    }
}
