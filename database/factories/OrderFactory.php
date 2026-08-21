<?php

namespace Database\Factories;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $address = ['first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'company' => '', 'address1' => fake()->streetAddress(), 'address2' => '', 'city' => fake()->city(), 'province' => '', 'province_code' => '', 'country' => 'Germany', 'country_code' => 'DE', 'zip' => fake()->postcode(), 'phone' => ''];

        return ['store_id' => Store::factory(), 'customer_id' => Customer::factory(), 'order_number' => '#'.fake()->unique()->numberBetween(1001, 9999), 'currency' => 'EUR', 'status' => OrderStatus::Paid, 'financial_status' => FinancialStatus::Paid, 'fulfillment_status' => FulfillmentStatus::Unfulfilled, 'payment_method' => 'credit_card', 'email' => fake()->safeEmail(), 'shipping_address_json' => $address, 'billing_address_json' => $address, 'subtotal_amount' => 4998, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 798, 'total_amount' => 5497, 'placed_at' => now(), 'metadata' => []];
    }

    public function pending(): static
    {
        return $this->state(['status' => OrderStatus::Pending, 'financial_status' => FinancialStatus::Pending]);
    }

    public function pendingBankTransfer(): static
    {
        return $this->pending()->bankTransfer();
    }

    public function fulfilled(): static
    {
        return $this->state(['status' => OrderStatus::Fulfilled, 'fulfillment_status' => FulfillmentStatus::Fulfilled]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => OrderStatus::Cancelled, 'financial_status' => FinancialStatus::Refunded]);
    }

    public function refunded(): static
    {
        return $this->state(['status' => OrderStatus::Refunded, 'financial_status' => FinancialStatus::Refunded]);
    }

    public function partiallyFulfilled(): static
    {
        return $this->state(['fulfillment_status' => FulfillmentStatus::Partial]);
    }

    public function creditCard(): static
    {
        return $this->state(['payment_method' => 'credit_card']);
    }

    public function paypal(): static
    {
        return $this->state(['payment_method' => 'paypal']);
    }

    public function bankTransfer(): static
    {
        return $this->state(['payment_method' => 'bank_transfer']);
    }
}
