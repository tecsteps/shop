<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Payment>
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
            'amount' => fn (array $attributes) => Order::find($attributes['order_id'])?->total_amount ?? 0,
            'currency' => 'USD',
            'raw_json_encrypted' => null,
        ];
    }

    /**
     * Indicate that the payment is pending (bank transfer).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => PaymentMethod::BankTransfer,
            'status' => PaymentStatus::Pending,
        ]);
    }
}
