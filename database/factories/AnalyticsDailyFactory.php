<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticsDailyFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'date' => fake()->date(),
            'orders_count' => fake()->numberBetween(0, 1000),
            'revenue_amount' => fake()->numberBetween(0, 1000000),
            'aov_amount' => fake()->numberBetween(0, 50000),
            'visits_count' => fake()->numberBetween(0, 10000),
            'add_to_cart_count' => fake()->numberBetween(0, 2000),
            'checkout_started_count' => fake()->numberBetween(0, 1000),
            'checkout_completed_count' => fake()->numberBetween(0, 1000),
        ];
    }
}
