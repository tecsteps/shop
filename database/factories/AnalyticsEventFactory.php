<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    protected $model = AnalyticsEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['page_view', 'product_view', 'add_to_cart', 'remove_from_cart', 'checkout_started', 'checkout_completed', 'search']),
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'client_event_id' => fake()->unique()->uuid(),
            'payload' => ['url' => '/'.fake()->slug(), 'referrer' => fake()->boolean(40) ? fake()->url() : null],
            'properties_json' => [],
            'occurred_at' => now()->subDays(fake()->numberBetween(0, 6))->subMinutes(fake()->numberBetween(0, 1439)),
        ];
    }

    public function pageView(): static
    {
        return $this->state(['type' => 'page_view']);
    }

    public function productView(int $productId, string $productTitle): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'product_view', 'payload' => array_merge($attributes['payload'] ?? [], ['product_id' => $productId, 'product_title' => $productTitle])]);
    }

    public function addToCart(int $variantId, int $quantity = 1): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'add_to_cart', 'payload' => array_merge($attributes['payload'] ?? [], ['variant_id' => $variantId, 'quantity' => $quantity])]);
    }
}
