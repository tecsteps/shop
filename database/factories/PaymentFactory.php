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
            'provider_payment_id' => 'mock_'.Str::random(12),
            'status' => PaymentStatus::Captured->value,
            'amount' => 1000,
            'currency' => 'USD',
            'raw_json_encrypted' => null,
            'created_at' => now(),
        ];
    }
}
