<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckoutFactory extends Factory
{
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
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
            ],
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'payment_method' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => null,
            'expires_at' => now()->addDay(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'completed']);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'expired', 'expires_at' => now()->subHour()]);
    }

    public function withCreditCard(): static
    {
        return $this->state(fn (array $attributes) => ['payment_method' => 'credit_card']);
    }

    public function withPaypal(): static
    {
        return $this->state(fn (array $attributes) => ['payment_method' => 'paypal']);
    }

    public function withBankTransfer(): static
    {
        return $this->state(fn (array $attributes) => ['payment_method' => 'bank_transfer']);
    }
}
