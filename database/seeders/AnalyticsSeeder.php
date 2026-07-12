<?php

namespace Database\Seeders;

use App\Models\AnalyticsDaily;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        foreach (range(29, 0) as $offset) {
            $orders = 3 + (($offset * 7) % 10);
            $revenue = $orders * (6500 + (($offset * 137) % 4000));
            DB::table('analytics_daily')->updateOrInsert(['store_id' => $store->id, 'date' => now()->subDays($offset)->toDateString()], [
                'orders_count' => $orders, 'revenue_amount' => $revenue, 'aov_amount' => intdiv($revenue, $orders),
                'visits_count' => 90 + (($offset * 17) % 120), 'add_to_cart_count' => 20 + (($offset * 5) % 35),
                'checkout_started_count' => 10 + (($offset * 3) % 20), 'checkout_completed_count' => $orders,
            ]);
        }
    }
}
