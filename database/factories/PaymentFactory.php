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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'mock',
            'method' => PaymentMethod::CreditCard->value,
            'provider_payment_id' => 'mock_'.Str::lower(Str::random(24)),
            'status' => PaymentStatus::Captured->value,
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'USD',
            'raw_json_encrypted' => ['outcome' => 'captured'],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::BankTransfer->value,
            'status' => PaymentStatus::Pending->value,
        ]);
    }
}
