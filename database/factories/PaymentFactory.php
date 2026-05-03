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
            'provider_payment_id' => 'mock_'.Str::random(16),
            'status' => PaymentStatus::Captured,
            'amount' => 6545,
            'currency' => 'EUR',
            'raw_json_encrypted' => [
                'reference' => 'mock_'.Str::random(16),
                'status' => PaymentStatus::Captured->value,
            ],
        ];
    }

    public function pendingBankTransfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
        ]);
    }
}
