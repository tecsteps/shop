<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AnalyticsEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement([
                'page_view', 'product_view', 'add_to_cart', 'remove_from_cart',
                'checkout_started', 'checkout_completed', 'search',
            ]),
            'session_id' => (string) Str::uuid(),
            'customer_id' => null,
            'properties_json' => [
                'url' => '/'.fake()->slug(),
                'referrer' => fake()->boolean(40) ? fake()->url() : null,
            ],
            'client_event_id' => (string) Str::uuid(),
            'occurred_at' => fake()->dateTimeBetween('-7 days'),
            'created_at' => fake()->dateTimeBetween('-7 days'),
        ];
    }

    public function pageView(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'page_view']);
    }

    public function productView(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product_view',
            'properties_json' => ['product_id' => 1, 'product_title' => fake()->words(3, true)],
        ]);
    }

    public function addToCart(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'add_to_cart',
            'properties_json' => ['variant_id' => 1, 'quantity' => 1],
        ]);
    }
}
