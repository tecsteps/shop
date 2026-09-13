<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckoutFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $address = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => null,
            'address1' => fake()->streetAddress(),
            'address2' => null,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'province_code' => fake()->stateAbbr(),
            'country' => 'United States',
            'country_code' => 'US',
            'zip' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
        ];

        return [
            'store_id' => Store::factory(),
            'cart_id' => Cart::factory(),
            'customer_id' => null,
            'status' => fake()->randomElement(CheckoutStatus::cases()),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'email' => fake()->safeEmail(),
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'shipping_method_id' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 799, 'tax' => 430, 'total' => 6229],
            'expires_at' => fake()->dateTimeBetween('+30 minutes', '+2 hours'),
        ];
    }
}
