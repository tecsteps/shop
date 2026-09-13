<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => PaymentProvider::Mock,
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'provider_payment_id' => 'mock_'.Str::random(12),
            'status' => fake()->randomElement(PaymentStatus::cases()),
            'amount' => fake()->numberBetween(100, 50000),
            'currency' => 'USD',
            'raw_json_encrypted' => json_encode(['id' => fake()->uuid(), 'status' => 'succeeded']),
        ];
    }
}
