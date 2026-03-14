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
            'order_number' => (string) fake()->unique()->numberBetween(1000, 99999),
            'email' => fake()->safeEmail(),
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => PaymentMethod::CreditCard,
            'currency' => 'EUR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'tax_amount' => 950,
            'total_amount' => 6450,
            'placed_at' => now(),
        ];
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
        return $this->paid()->state(fn (array $attributes) => [
            'status' => OrderStatus::Fulfilled,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => 'Customer requested cancellation',
        ]);
    }
}
