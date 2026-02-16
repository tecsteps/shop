<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => PaymentMethod::CreditCard,
            'provider' => 'mock',
            'provider_payment_id' => 'mock_'.fake()->sha1(),
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'USD',
            'status' => PaymentStatus::Captured,
            'captured_at' => now(),
        ];
    }
}
