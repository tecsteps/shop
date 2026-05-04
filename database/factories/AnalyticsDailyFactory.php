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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orders = fake()->numberBetween(0, 12);
        $revenue = $orders * fake()->numberBetween(2500, 9500);

        return [
            'store_id' => Store::factory(),
            'date' => now()->toDateString(),
            'orders_count' => $orders,
            'revenue_amount' => $revenue,
            'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
            'visits_count' => fake()->numberBetween(20, 200),
            'add_to_cart_count' => fake()->numberBetween(0, 40),
            'checkout_started_count' => fake()->numberBetween(0, 20),
            'checkout_completed_count' => $orders,
        ];
    }
}
