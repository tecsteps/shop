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

        Discount::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'code' => 'WELCOME10',
            ],
            [
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Percent,
                'value_amount' => 10,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addYear(),
                'usage_limit' => null,
                'usage_count' => 0,
                'rules_json' => [
                    'min_purchase_amount' => 1000,
                    'applicable_product_ids' => null,
                    'applicable_collection_ids' => null,
                    'customer_eligibility' => 'all',
                ],
                'status' => DiscountStatus::Active,
            ],
        );
    }
}
