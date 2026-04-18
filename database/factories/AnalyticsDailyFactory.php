<?php

namespace Database\Factories;

use App\Models\AnalyticsDaily;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsDaily>
 */
class AnalyticsDailyFactory extends Factory
{
    protected $model = AnalyticsDaily::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'date' => now()->toDateString(),
            'orders_count' => 0,
            'revenue_amount' => 0,
            'aov_amount' => 0,
            'visits_count' => 0,
            'add_to_cart_count' => 0,
            'checkout_started_count' => 0,
            'checkout_completed_count' => 0,
        ];
    }
}
