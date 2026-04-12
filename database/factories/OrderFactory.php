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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = 5000;

        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1000, 999999),
            'payment_method' => PaymentMethod::CreditCard->value,
            'status' => OrderStatus::Pending->value,
            'financial_status' => FinancialStatus::Pending->value,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => 599,
            'tax_amount' => 0,
            'total_amount' => $subtotal + 599,
            'email' => fake()->safeEmail(),
            'billing_address_json' => null,
            'shipping_address_json' => null,
            'placed_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid->value,
            'financial_status' => FinancialStatus::Paid->value,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Cancelled->value,
        ]);
    }
}
