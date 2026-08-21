<?php

namespace Database\Seeders;

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
        $discounts = [
            ['code' => 'WELCOME10', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'usage_count' => 3, 'rules_json' => ['min_purchase_amount' => 2000], 'status' => 'active'],
            ['code' => 'FLAT5', 'value_type' => DiscountValueType::Fixed, 'value_amount' => 500, 'usage_count' => 0, 'rules_json' => [], 'status' => 'active'],
            ['code' => 'FREESHIP', 'value_type' => DiscountValueType::FreeShipping, 'value_amount' => 0, 'usage_count' => 1, 'rules_json' => [], 'status' => 'active'],
            ['code' => 'EXPIRED20', 'value_type' => DiscountValueType::Percent, 'value_amount' => 20, 'usage_count' => 0, 'rules_json' => [], 'status' => 'expired', 'starts_at' => now()->subYears(2), 'ends_at' => now()->subYear()],
            ['code' => 'MAXED', 'value_type' => DiscountValueType::Percent, 'value_amount' => 10, 'usage_count' => 5, 'usage_limit' => 5, 'rules_json' => [], 'status' => 'active'],
        ];

        foreach ($discounts as $discount) {
            Discount::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey(), 'code' => $discount['code']],
                ['type' => 'code', 'value_type' => $discount['value_type'], 'value_amount' => $discount['value_amount'], 'status' => $discount['status'], 'usage_limit' => $discount['usage_limit'] ?? null, 'usage_count' => $discount['usage_count'], 'starts_at' => $discount['starts_at'] ?? now()->subYear(), 'ends_at' => $discount['ends_at'] ?? now()->addYear(), 'rules_json' => $discount['rules_json']],
            );
        }
    }
}
