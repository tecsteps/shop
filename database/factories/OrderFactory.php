<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
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
            'customer_id' => null,
            'checkout_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 999999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentOrderStatus::Unfulfilled,
            'currency' => 'USD',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'email' => fake()->safeEmail(),
            'billing_address_json' => [],
            'shipping_address_json' => [],
            'placed_at' => now(),
        ];
    }
}
