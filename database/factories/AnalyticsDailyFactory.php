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
        $orders = fake()->numberBetween(0, 8);
        $revenue = $orders * fake()->numberBetween(4000, 9000);

        return [
            'store_id' => Store::factory(),
            'date' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),
            'orders_count' => $orders,
            'revenue_amount' => $revenue,
            'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
            'visits_count' => fake()->numberBetween(50, 200),
            'add_to_cart_count' => fake()->numberBetween(10, 50),
            'checkout_started_count' => fake()->numberBetween(4, 25),
            'checkout_completed_count' => $orders,
        ];
    }
}
