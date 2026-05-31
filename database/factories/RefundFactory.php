<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'amount' => fake()->numberBetween(500, 10000),
            'reason' => fake()->sentence(),
            'status' => RefundStatus::Processed->value,
            'provider_refund_id' => 'mock_'.Str::lower(Str::random(24)),
        ];
    }
}
