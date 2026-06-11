<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /**
     * Define the model's default state (spec 07 section 2.26).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => $this->faker->randomElement([
                'page_view', 'product_view', 'add_to_cart', 'remove_from_cart',
                'checkout_started', 'checkout_completed', 'search',
            ]),
            'session_id' => $this->faker->uuid(),
            'customer_id' => null,
            'properties_json' => [
                'url' => '/'.$this->faker->slug(),
                'referrer' => $this->faker->boolean(40) ? $this->faker->url() : null,
            ],
            'created_at' => $this->faker->dateTimeBetween('-7 days'),
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
            'properties_json' => [
                'product_id' => $this->faker->numberBetween(1, 100),
                'product_title' => $this->faker->words(3, true),
                'url' => '/products/'.$this->faker->slug(),
            ],
        ]);
    }

    public function addToCart(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'add_to_cart',
            'properties_json' => [
                'variant_id' => $this->faker->numberBetween(1, 200),
                'quantity' => $this->faker->numberBetween(1, 3),
            ],
        ]);
    }
}
