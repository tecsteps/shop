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
        $ordersCount = $this->faker->numberBetween(2, 8);
        $aovAmount = $this->faker->numberBetween(4000, 9000);
        $visitsCount = $this->faker->numberBetween(50, 190);
        $addToCartCount = (int) round($visitsCount * 0.2);
        $checkoutStartedCount = (int) round($addToCartCount * 0.5);

        return [
            'store_id' => Store::factory(),
            'date' => $this->faker->unique()->dateTimeBetween('-90 days')->format('Y-m-d'),
            'orders_count' => $ordersCount,
            'revenue_amount' => $ordersCount * $aovAmount,
            'aov_amount' => $aovAmount,
            'visits_count' => $visitsCount,
            'add_to_cart_count' => $addToCartCount,
            'checkout_started_count' => $checkoutStartedCount,
            'checkout_completed_count' => $ordersCount,
        ];
    }

    /**
     * Pin the row to a specific ISO date.
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }
}
