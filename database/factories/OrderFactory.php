<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1000, 50000);
        $discount = fake()->numberBetween(0, (int) ($subtotal / 4));
        $shipping = fake()->numberBetween(0, 2000);
        $tax = fake()->numberBetween(0, 5000);
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
            'customer_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 999999),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => fake()->randomElement(OrderStatus::cases()),
            'financial_status' => fake()->randomElement(FinancialStatus::cases()),
            'fulfillment_status' => fake()->randomElement(FulfillmentStatus::cases()),
            'currency' => 'USD',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $subtotal - $discount + $shipping + $tax,
            'email' => fake()->safeEmail(),
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => fake()->dateTime(),
        ];
    }
}
