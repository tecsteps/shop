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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'session_id' => fake()->uuid(),
            'event_type' => 'page_view',
            'properties_json' => [],
        ];
    }

    /**
     * Indicate that the event is a product view.
     */
    public function productView(int $productId = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'product_view',
            'properties_json' => ['product_id' => $productId],
        ]);
    }

    /**
     * Indicate that the event is an add to cart.
     */
    public function addToCart(int $productId = 1, int $variantId = 1, int $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'add_to_cart',
            'properties_json' => [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ],
        ]);
    }

    /**
     * Indicate that the event is a checkout started.
     */
    public function checkoutStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'checkout_started',
        ]);
    }

    /**
     * Indicate that the event is a checkout completed.
     */
    public function checkoutCompleted(int $orderId = 1, int $totalAmount = 5000): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'checkout_completed',
            'properties_json' => [
                'order_id' => $orderId,
                'total_amount' => $totalAmount,
            ],
        ]);
    }
}
