<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Checkout>
 */
class CheckoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => fn (array $attributes) => Cart::find($attributes['cart_id'])?->store_id,
            'cart_id' => Cart::factory(),
            'customer_id' => null,
            'status' => CheckoutStatus::Started,
            'payment_method' => null,
            'email' => fake()->safeEmail(),
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => null,
            'expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Indicate that the checkout has an address set.
     */
    public function addressed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Addressed,
        ]);
    }

    /**
     * Indicate that the checkout is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Expired,
            'expires_at' => now()->subHour(),
        ]);
    }
}
