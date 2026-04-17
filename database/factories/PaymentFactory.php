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
            'method' => PaymentMethod::CreditCard->value,
            'provider_payment_id' => 'mock_'.Str::random(12),
            'status' => PaymentStatus::Captured->value,
            'amount' => 5000,
            'currency' => 'EUR',
            'raw_json_encrypted' => null,
        ];
    }

    public function captured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Captured->value,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Failed->value,
        ]);
    }
}
