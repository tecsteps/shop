<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Checkout>
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
            'email' => fake()->safeEmail(),
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'DE',
                'postal_code' => fake()->postcode(),
            ],
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => null,
            'expires_at' => now()->addDay(),
        ];
    }

    /**
     * Mark the checkout as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Completed,
        ]);
    }

    /**
     * Mark the checkout as expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Expired,
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Select credit card as the payment method.
     */
    public function withCreditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credit_card',
        ]);
    }

    /**
     * Select PayPal as the payment method.
     */
    public function withPaypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'paypal',
        ]);
    }

    /**
     * Select bank transfer as the payment method.
     */
    public function withBankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_transfer',
        ]);
    }
}
