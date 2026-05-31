<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1000, 50000);

        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'checkout_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 99999),
            'payment_method' => PaymentMethod::CreditCard->value,
            'status' => OrderStatus::Paid->value,
            'financial_status' => FinancialStatus::Paid->value,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
            'currency' => 'USD',
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 0,
            'total_amount' => $subtotal + 499,
            'email' => fake()->safeEmail(),
            'billing_address_json' => null,
            'shipping_address_json' => null,
            'placed_at' => Carbon::now(),
        ];
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::BankTransfer->value,
            'status' => OrderStatus::Pending->value,
            'financial_status' => FinancialStatus::Pending->value,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid->value,
            'financial_status' => FinancialStatus::Paid->value,
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Fulfilled->value,
            'fulfillment_status' => FulfillmentStatus::Fulfilled->value,
        ]);
    }
}
