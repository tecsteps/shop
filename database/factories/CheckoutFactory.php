<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checkout>
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
            'store_id' => Store::factory(),
            'cart_id' => Cart::factory(),
            'customer_id' => null,
            'status' => CheckoutStatus::Started,
            'payment_method' => null,
            'email' => null,
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'totals_json' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Checkout in addressed state.
     */
    public function addressed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Addressed,
            'email' => fake()->safeEmail(),
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country' => 'US',
                'postal_code' => fake()->postcode(),
            ],
            'billing_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country' => 'US',
                'postal_code' => fake()->postcode(),
            ],
        ]);
    }
}
