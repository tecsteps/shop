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
            'customer_id' => null,
            'session_id' => fake()->uuid(),
            'event_type' => fake()->randomElement([
                'page_view', 'product_view', 'add_to_cart',
                'remove_from_cart', 'checkout_started', 'checkout_completed', 'search',
            ]),
            'properties_json' => [],
        ];
    }

    public function pageView(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => 'page_view',
        ]);
    }

    public function addToCart(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => 'add_to_cart',
        ]);
    }

    public function checkoutCompleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => 'checkout_completed',
        ]);
    }

    public function productView(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => 'product_view',
            'properties_json' => [
                'product_id' => fake()->randomNumber(),
                'product_title' => fake()->words(3, true),
                'url' => '/products/'.fake()->slug(3),
            ],
        ]);
    }

    public function checkoutStarted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => 'checkout_started',
        ]);
    }
}
