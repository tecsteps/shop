<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1000, 99999),
            'payment_method' => PaymentMethod::CreditCard->value,
            'status' => OrderStatus::Pending->value,
            'financial_status' => FinancialStatus::Pending->value,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
            'currency' => 'USD',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'email' => fake()->safeEmail(),
            'billing_address_json' => null,
            'shipping_address_json' => null,
            'placed_at' => now(),
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid->value,
            'financial_status' => FinancialStatus::Paid->value,
        ]);
    }
}
