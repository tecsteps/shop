<?php

namespace Database\Seeders;

use App\Models\AnalyticsDaily;
use App\Models\Store;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        if (AnalyticsDaily::where('store_id', $store->id)->exists()) {
            return;
        }

        for ($i = 30; $i >= 0; $i--) {
            $dayFactor = 1 + (30 - $i) * 0.03;
            $visits = (int) round(rand(50, 100) * $dayFactor);
            $addToCart = (int) round($visits * rand(18, 25) / 100);
            $checkoutStarted = (int) round($addToCart * rand(40, 55) / 100);
            $orders = max(2, (int) round($checkoutStarted * rand(35, 55) / 100));
            $aov = rand(4000, 9000);
            $revenue = $orders * $aov;

            AnalyticsDaily::create([
                'store_id' => $store->id,
                'date' => now()->subDays($i)->toDateString(),
                'visits_count' => $visits,
                'add_to_cart_count' => $addToCart,
                'checkout_started_count' => $checkoutStarted,
                'checkout_completed_count' => $orders,
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $aov,
            ]);
        }
    }
}
