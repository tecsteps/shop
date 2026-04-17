<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'amount' => 500,
            'reason' => 'test',
            'status' => RefundStatus::Processed->value,
            'provider_refund_id' => 'mock_refund_'.fake()->unique()->word(),
            'created_at' => now(),
        ];
    }
}
