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
            'order_number' => '1'.$this->faker->unique()->numberBetween(1000, 999999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => 'USD',
            'subtotal_amount' => 1000,
            'total_amount' => 1000,
            'placed_at' => now(),
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
