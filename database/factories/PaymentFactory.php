<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'mock',
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_'.fake()->regexify('[a-zA-Z0-9]{20}'),
            'status' => PaymentStatus::Captured,
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'EUR',
            'captured_at' => now(),
            'created_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
            'method' => PaymentMethod::BankTransfer,
            'captured_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'error_code' => 'card_declined',
            'error_message' => 'The card was declined.',
            'captured_at' => null,
        ]);
    }
}
