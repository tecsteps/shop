<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 9999),
            'email' => fake()->safeEmail(),
            'phone' => null,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => 'USD',
            'subtotal' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'tax_amount' => 950,
            'total' => 6450,
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'discount_code' => null,
            'payment_method' => 'credit_card',
            'note' => null,
            'placed_at' => now(),
            'totals_json' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
        ]);
    }
}
