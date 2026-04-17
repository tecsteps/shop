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
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => PaymentMethod::CreditCard,
            'provider' => 'mock',
            'provider_payment_id' => 'mock_'.fake()->uuid(),
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'EUR',
            'status' => PaymentStatus::Pending,
        ];
    }

    public function captured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Captured,
            'captured_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed,
            'error_code' => 'card_declined',
            'error_message' => 'The card was declined.',
        ]);
    }
}
