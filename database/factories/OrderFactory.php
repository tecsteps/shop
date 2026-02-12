<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $address = [
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
        ];

        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => (string) fake()->unique()->numberBetween(1001, 9999),
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'EUR',
            'subtotal_amount' => 4998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 798,
            'total_amount' => 5497,
            'email' => fake()->safeEmail(),
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'financial_status' => 'pending',
        ]);
    }

    public function pendingBankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'financial_status' => 'pending',
            'payment_method' => 'bank_transfer',
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'fulfilled',
            'fulfillment_status' => 'fulfilled',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'financial_status' => 'refunded',
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'financial_status' => 'refunded',
        ]);
    }

    public function partiallyFulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfillment_status' => 'partial',
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credit_card',
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'paypal',
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_transfer',
        ]);
    }
}
