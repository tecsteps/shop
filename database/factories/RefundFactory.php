<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RefundFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'amount' => fake()->numberBetween(100, 10000),
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(RefundStatus::cases()),
            'provider_refund_id' => 'mock_rf_'.Str::random(12),
        ];
    }
}
