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
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed']),
            'properties_json' => '{}',
            'session_id' => fake()->uuid(),
            'customer_id' => null,
        ];
    }

    public function pageView(): static
    {
        return $this->state(fn () => [
            'type' => 'page_view',
            'properties_json' => json_encode(['url' => '/']),
        ]);
    }

    public function productView(): static
    {
        return $this->state(fn () => [
            'type' => 'product_view',
            'properties_json' => json_encode(['product_id' => fake()->numberBetween(1, 100)]),
        ]);
    }

    public function addToCart(): static
    {
        return $this->state(fn () => [
            'type' => 'add_to_cart',
            'properties_json' => json_encode(['variant_id' => fake()->numberBetween(1, 100)]),
        ]);
    }

    public function checkoutStarted(): static
    {
        return $this->state(fn () => ['type' => 'checkout_started']);
    }

    public function checkoutCompleted(): static
    {
        return $this->state(fn () => [
            'type' => 'checkout_completed',
            'properties_json' => json_encode(['order_id' => fake()->numberBetween(1, 100)]),
        ]);
    }
}
