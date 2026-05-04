<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'mock',
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_payment_'.fake()->unique()->lexify('????????????????'),
            'status' => PaymentStatus::Captured,
            'amount' => fake()->numberBetween(2500, 25000),
            'currency' => 'EUR',
            'raw_json_encrypted' => [
                'success' => true,
                'status' => PaymentStatus::Captured->value,
            ],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Pending,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Failed,
            'raw_json_encrypted' => [
                'success' => false,
                'status' => PaymentStatus::Failed->value,
                'error_code' => 'card_declined',
            ],
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Refunded,
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => PaymentMethod::Paypal,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
            'provider_payment_id' => 'mock_bank_'.fake()->unique()->lexify('????????????????'),
        ]);
    }
}
