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
        $subtotal = fake()->numberBetween(1000, 50000);
        $shipping = fake()->randomElement([0, 499, 999]);
        $tax = intdiv($subtotal * 1900, 10000);
        $total = $subtotal + $shipping + $tax;

        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
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
            'billing_address_json' => $this->fakeAddress(),
            'shipping_address_json' => $this->fakeAddress(),
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

    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
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

    /**
     * @return array<string, mixed>
     */
    protected function fakeAddress(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address1' => fake()->streetAddress(),
            'address2' => null,
            'company' => null,
            'city' => fake()->city(),
            'province' => null,
            'province_code' => null,
            'country' => 'DE',
            'postal_code' => fake()->postcode(),
            'phone' => null,
        ];
    }
}
