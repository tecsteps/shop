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
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 9999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => 'EUR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 950,
            'total_amount' => 6449,
            'email' => fake()->safeEmail(),
            'placed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'payment_method' => PaymentMethod::BankTransfer,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'financial_status' => FinancialStatus::Voided,
            'cancelled_at' => now(),
            'cancel_reason' => 'Customer requested cancellation',
        ]);
    }
}
