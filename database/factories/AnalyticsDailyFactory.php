<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsDaily>
 */
class AnalyticsDailyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
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
