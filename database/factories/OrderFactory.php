<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => '#'.fake()->unique()->numberBetween(1000, 9999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => 'EUR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 798,
            'total_amount' => 5497,
            'email' => fake()->safeEmail(),
            'billing_address_json' => null,
            'shipping_address_json' => null,
            'placed_at' => now(),
        ];
    }
}
