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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'date' => fake()->date(),
            'orders_count' => fake()->numberBetween(0, 100),
            'revenue_amount' => fake()->numberBetween(0, 100000),
            'aov_amount' => fake()->numberBetween(0, 5000),
            'visits_count' => fake()->numberBetween(0, 1000),
            'add_to_cart_count' => fake()->numberBetween(0, 200),
            'checkout_started_count' => fake()->numberBetween(0, 50),
        ];
    }
}
