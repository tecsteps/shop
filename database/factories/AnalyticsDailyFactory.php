<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AnalyticsDaily>
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
            'date' => now()->toDateString(),
            'orders_count' => fake()->numberBetween(0, 20),
            'revenue_amount' => fake()->numberBetween(0, 100000),
            'aov_amount' => fake()->numberBetween(0, 5000),
            'visits_count' => fake()->numberBetween(0, 500),
            'add_to_cart_count' => fake()->numberBetween(0, 100),
            'checkout_started_count' => fake()->numberBetween(0, 50),
            'checkout_completed_count' => fake()->numberBetween(0, 20),
        ];
    }
}
