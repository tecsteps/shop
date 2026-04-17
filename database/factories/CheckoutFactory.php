<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Checkout> */
class CheckoutFactory extends Factory
{
    protected $model = Checkout::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'cart_id' => Cart::factory(),
            'customer_id' => null,
            'status' => CheckoutStatus::Started,
            'email' => fake()->safeEmail(),
            'expires_at' => now()->addHours(2),
        ];
    }

    public function addressed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Addressed,
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'province' => fake()->stateAbbr(),
                'country' => 'US',
                'zip' => fake()->postcode(),
            ],
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Completed,
            'payment_method' => 'credit_card',
            'totals_json' => [
                'subtotal' => 5000,
                'discount' => 0,
                'shipping' => 799,
                'tax' => 430,
                'total' => 6229,
            ],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Expired,
            'expires_at' => now()->subHour(),
        ]);
    }
}
