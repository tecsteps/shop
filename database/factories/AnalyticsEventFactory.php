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
    protected $model = AnalyticsEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement([
                'page_view', 'product_view', 'add_to_cart',
                'remove_from_cart', 'checkout_started', 'checkout_completed', 'search',
            ]),
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => null,
            'occurred_at' => now(),
            'created_at' => now(),
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
            'properties_json' => ['product_id' => fake()->numberBetween(1, 100)],
        ]);
    }

    public function checkoutCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkout_completed',
            'properties_json' => ['order_id' => fake()->numberBetween(1, 100)],
        ]);
    }

    public function checkoutStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkout_started',
        ]);
    }
}
