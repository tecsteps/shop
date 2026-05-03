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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'checkout_id' => null,
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 9999),
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => 'EUR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 500,
            'tax_amount' => 1045,
            'total_amount' => 6545,
            'email' => fake()->safeEmail(),
            'billing_address_json' => $this->address(),
            'shipping_address_json' => $this->address(),
            'placed_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
        ]);
    }

    public function bankTransferPending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
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
            'province' => fake()->state(),
            'province_code' => fake()->stateAbbr(),
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => fake()->postcode(),
            'phone' => null,
        ];
    }
}
