<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'provider_payment_id' => 'mock_'.Str::lower(Str::random(16)),
            'status' => PaymentStatus::Captured,
            'amount' => 5000,
            'currency' => 'USD',
        ];
    }

    /**
     * A captured (successful) payment.
     */
    public function captured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Captured,
        ]);
    }

    /**
     * A pending payment (bank transfer awaiting confirmation).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * A fully refunded payment.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Refunded,
        ]);
    }
}
