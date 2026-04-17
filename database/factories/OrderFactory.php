<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'order_number' => '#'.fake()->unique()->numerify('10####'),
            'payment_method' => PaymentMethod::CreditCard->value,
            'status' => OrderStatus::Open->value,
            'financial_status' => FinancialStatus::Paid->value,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
            'currency' => 'EUR',
            'subtotal_amount' => 2499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 475,
            'total_amount' => 3473,
            'email' => fake()->safeEmail(),
            'placed_at' => now(),
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'zip' => fake()->postcode(),
                'country' => 'DE',
            ],
            'billing_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'zip' => fake()->postcode(),
                'country' => 'DE',
            ],
        ];
    }
}
