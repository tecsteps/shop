<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::query()->where('handle', 'acme-fashion')->sole();
            foreach ([
                ['WELCOME10', 'percent', 10, '2025-01-01', '2027-12-31', null, 3, ['min_purchase_amount' => 2000], 'active'],
                ['FLAT5', 'fixed', 500, '2025-01-01', '2027-12-31', null, 0, [], 'active'],
                ['FREESHIP', 'free_shipping', 0, '2025-01-01', '2027-12-31', null, 1, [], 'active'],
                ['EXPIRED20', 'percent', 20, '2024-01-01', '2024-12-31', null, 0, [], 'expired'],
                ['MAXED', 'percent', 10, '2025-01-01', '2027-12-31', 5, 5, [], 'active'],
            ] as [$code, $valueType, $valueAmount, $startsAt, $endsAt, $usageLimit, $usageCount, $rules, $status]) {
                Discount::withoutGlobalScopes()->updateOrCreate(
                    ['store_id' => $store->id, 'code' => $code],
                    [
                        'type' => 'code',
                        'value_type' => $valueType,
                        'value_amount' => $valueAmount,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'usage_limit' => $usageLimit,
                        'usage_count' => $usageCount,
                        'rules_json' => $rules,
                        'status' => $status,
                    ],
                );
            }
        });
    }
}
