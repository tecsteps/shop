<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

            foreach ([
                ['WELCOME10', 'percent', 10, '2025-01-01', '2027-12-31', null, 3, ['min_purchase_amount' => 2000], 'active'],
                ['FLAT5', 'fixed', 500, '2025-01-01', '2027-12-31', null, 0, [], 'active'],
                ['FREESHIP', 'free_shipping', 0, '2025-01-01', '2027-12-31', null, 1, [], 'active'],
                ['EXPIRED20', 'percent', 20, '2024-01-01', '2024-12-31', null, 0, [], 'expired'],
                ['MAXED', 'percent', 10, '2025-01-01', '2027-12-31', 5, 5, [], 'active'],
            ] as [$code, $valueType, $value, $startsAt, $endsAt, $limit, $count, $rules, $status]) {
                Discount::query()->updateOrCreate(
                    ['store_id' => $store->id, 'code' => $code],
                    [
                        'type' => 'code',
                        'value_type' => $valueType,
                        'value_amount' => $value,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'usage_limit' => $limit,
                        'usage_count' => $count,
                        'rules_json' => $rules,
                        'status' => $status,
                    ],
                );
            }
        });
    }
}
