<?php

namespace Database\Seeders;

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
        Store::query()->get()->each(function (Store $store): void {
            foreach ($this->discounts() as $discount) {
                Discount::withoutGlobalScopes()->updateOrCreate(
                    [
                        'store_id' => $store->getKey(),
                        'code' => $discount['code'],
                    ],
                    [
                        'type' => 'code',
                        'value_type' => $discount['value_type'],
                        'value_amount' => $discount['value_amount'],
                        'starts_at' => $discount['starts_at'] ?? now()->subDay(),
                        'ends_at' => $discount['ends_at'] ?? now()->addMonth(),
                        'usage_limit' => $discount['usage_limit'] ?? null,
                        'usage_count' => $discount['usage_count'] ?? 0,
                        'rules_json' => $discount['rules_json'],
                        'status' => $discount['status'] ?? 'active',
                    ],
                );
            }
        });
    }

    /**
     * @return array<int, array{code: string, value_type: string, value_amount: int, rules_json: array<string, mixed>, starts_at?: \Illuminate\Support\Carbon, ends_at?: \Illuminate\Support\Carbon, usage_limit?: int|null, usage_count?: int, status?: string}>
     */
    private function discounts(): array
    {
        return [
            ['code' => 'WELCOME10', 'value_type' => 'percent', 'value_amount' => 10, 'usage_count' => 3, 'rules_json' => ['min_purchase_amount' => 2000]],
            ['code' => 'FLAT5', 'value_type' => 'fixed', 'value_amount' => 500, 'rules_json' => []],
            ['code' => 'SAVE10', 'value_type' => 'percent', 'value_amount' => 10, 'rules_json' => ['customer_eligibility' => 'all']],
            ['code' => '5OFF', 'value_type' => 'fixed', 'value_amount' => 500, 'rules_json' => ['min_purchase_amount' => 2500, 'customer_eligibility' => 'all']],
            ['code' => 'FREESHIP', 'value_type' => 'free_shipping', 'value_amount' => 0, 'rules_json' => ['customer_eligibility' => 'all']],
            ['code' => 'EXPIRED20', 'value_type' => 'percent', 'value_amount' => 20, 'ends_at' => now()->subDay(), 'rules_json' => [], 'status' => 'expired'],
            ['code' => 'MAXED', 'value_type' => 'percent', 'value_amount' => 10, 'usage_limit' => 5, 'usage_count' => 5, 'rules_json' => []],
        ];
    }
}
