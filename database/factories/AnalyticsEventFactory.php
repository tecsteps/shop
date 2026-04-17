<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AnalyticsEvent> */
class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'checkout_started', 'checkout_completed']),
            'session_id' => Str::uuid()->toString(),
            'customer_id' => null,
            'properties_json' => [],
            'client_event_id' => Str::uuid()->toString(),
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

    public function productView(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product_view',
            'properties_json' => ['product_id' => fake()->numberBetween(1, 100)],
        ]);
    }

    public function addToCart(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'add_to_cart',
            'properties_json' => ['product_id' => fake()->numberBetween(1, 100), 'variant_id' => fake()->numberBetween(1, 100)],
        ]);
    }

    public function checkoutStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkout_started',
        ]);
    }

    public function checkoutCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checkout_completed',
            'properties_json' => ['order_total' => fake()->numberBetween(1000, 50000)],
        ]);
    }
}
