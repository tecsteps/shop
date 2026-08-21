<?php

namespace Database\Factories;

use App\Models\AnalyticsDaily;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsDaily>
 */
class AnalyticsDailyFactory extends Factory
{
    protected $model = AnalyticsDaily::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'date' => today()->subDays(fake()->numberBetween(0, 30)),
            'orders_count' => fake()->numberBetween(2, 8),
            'revenue_amount' => fake()->numberBetween(7000, 65000),
            'aov_amount' => fake()->numberBetween(4000, 9000),
            'visits_count' => fake()->numberBetween(50, 190),
            'add_to_cart_count' => fake()->numberBetween(10, 45),
            'checkout_started_count' => fake()->numberBetween(4, 25),
            'checkout_completed_count' => fake()->numberBetween(2, 8),
        ];
    }
}
