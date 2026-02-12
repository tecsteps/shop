<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Checkout> */
class CheckoutFactory extends Factory
{
    protected $model = Checkout::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'cart_id' => Cart::factory(),
            'customer_id' => null,
            'status' => 'started',
            'email' => fake()->safeEmail(),
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'company' => '',
                'address1' => fake()->streetAddress(),
                'address2' => '',
                'city' => fake()->city(),
                'province' => '',
                'province_code' => '',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
                'phone' => '',
            ],
            'billing_address_json' => null,
            'payment_method' => null,
            'discount_code' => null,
            'totals_json' => null,
            'expires_at' => now()->addHours(24),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subHour(),
        ]);
    }

    public function withCreditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credit_card',
        ]);
    }

    public function withPaypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'paypal',
        ]);
    }

    public function withBankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_transfer',
        ]);
    }
}
