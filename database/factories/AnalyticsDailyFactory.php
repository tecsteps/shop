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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'date' => fake()->date(),
            'orders_count' => fake()->numberBetween(0, 50),
            'revenue_amount' => fake()->numberBetween(0, 500000),
            'aov_amount' => fake()->numberBetween(0, 10000),
            'visits_count' => fake()->numberBetween(0, 1000),
            'add_to_cart_count' => fake()->numberBetween(0, 100),
            'checkout_started_count' => fake()->numberBetween(0, 50),
            'checkout_completed_count' => fake()->numberBetween(0, 30),
        ];
    }
}
