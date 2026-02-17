<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
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
            'amount' => fake()->numberBetween(100, 5000),
            'currency' => 'EUR',
            'reason' => fake()->optional()->sentence(),
            'status' => RefundStatus::Processed,
            'restock' => false,
            'provider_refund_id' => 'mock_refund_'.fake()->regexify('[a-zA-Z0-9]{20}'),
            'processed_at' => now(),
            'created_at' => now(),
        ];
    }
}
