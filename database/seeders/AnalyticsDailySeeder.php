<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsDailySeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        for ($i = 30; $i >= 0; $i--) {
            $dayFactor = 1 + (30 - $i) * 0.03;
            $visits = (int) round(fake()->numberBetween(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * fake()->numberBetween(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * fake()->numberBetween(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * fake()->numberBetween(35, 55) / 100));
            $aov = fake()->numberBetween(4000, 9000);
            $revenue = $orders * $aov;

            DB::table('analytics_daily')->updateOrInsert(
                [
                    'store_id' => $store->id,
                    'date' => now()->subDays($i)->toDateString(),
                ],
                [
                    'visits_count' => $visits,
                    'add_to_cart_count' => $addToCart,
                    'checkout_started_count' => $checkoutStarted,
                    'checkout_completed_count' => $orders,
                    'orders_count' => $orders,
                    'revenue_amount' => $revenue,
                    'aov_amount' => $aov,
                ],
            );
        }
    }
}
