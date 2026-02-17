<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed']),
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'properties_json' => [],
        ];
    }

    public function pageView(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'page_view',
            'properties_json' => ['url' => '/'],
        ]);
    }

    public function addToCart(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'add_to_cart',
            'properties_json' => ['variant_id' => 1, 'quantity' => 1],
        ]);
    }

    public function checkoutCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkout_completed',
            'properties_json' => ['order_id' => 1, 'total' => 5000],
        ]);
    }
}
