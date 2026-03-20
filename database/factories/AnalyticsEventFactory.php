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
            'properties_json' => '{}',
            'client_event_id' => fake()->unique()->uuid(),
            'occurred_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function pageView(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'page_view',
        ]);
    }

    public function productView(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product_view',
        ]);
    }

    public function addToCart(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'add_to_cart',
        ]);
    }

    public function checkoutStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkout_started',
        ]);
    }

    public function checkoutCompleted(int $orderTotal = 5000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkout_completed',
            'properties_json' => json_encode(['order_total' => $orderTotal]),
        ]);
    }
}
