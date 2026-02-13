<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'provider' => 'mock',
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_'.Str::random(16),
            'status' => PaymentStatus::Captured,
            'amount' => fake()->numberBetween(1000, 100000),
            'currency' => 'USD',
            'raw_json_encrypted' => null,
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
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Refunded,
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => PaymentMethod::CreditCard,
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
        ]);
    }
}
