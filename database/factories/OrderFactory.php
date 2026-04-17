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
        $subtotal = fake()->numberBetween(1000, 100000);
        $shipping = fake()->numberBetween(0, 1500);
        $tax = (int) round($subtotal * 0.19);
        $total = $subtotal + $shipping + $tax;

        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 99999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'email' => fake()->safeEmail(),
            'billing_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
            ],
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
            ],
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
        return $this->paid()->state(fn (array $attributes) => [
            'status' => OrderStatus::Fulfilled,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'financial_status' => FinancialStatus::Voided,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
        ]);
    }
}
