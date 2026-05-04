<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2500, 25000);
        $shipping = fake()->randomElement([0, 499, 799]);
        $tax = 0;

        return [
            'store_id' => Store::factory(),
            'checkout_id' => null,
            'customer_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 9999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $shipping + $tax,
            'email' => fake()->safeEmail(),
            'billing_address_json' => $this->address(),
            'shipping_address_json' => $this->address(),
            'placed_at' => now(),
        ];
    }

    public function forCheckout(?Checkout $checkout = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'checkout_id' => $checkout?->getKey() ?? Checkout::factory(),
        ]);
    }

    public function forCustomer(?Customer $customer = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_id' => $customer?->getKey() ?? Customer::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::CreditCard,
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::Paypal,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Cancelled,
            'financial_status' => FinancialStatus::Voided,
        ]);
    }

    public function partiallyFulfilled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'fulfillment_status' => FulfillmentStatus::Partial,
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Refunded,
            'financial_status' => FinancialStatus::Refunded,
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function address(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address1' => fake()->streetAddress(),
            'address2' => null,
            'city' => fake()->city(),
            'province_code' => null,
            'country' => 'DE',
            'postal_code' => fake()->postcode(),
        ];
    }
}
