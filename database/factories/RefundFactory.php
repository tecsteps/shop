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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'amount' => 5000,
            'reason' => null,
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_re_'.Str::lower(Str::random(16)),
        ];
    }

    /**
     * A refund still awaiting provider confirmation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RefundStatus::Pending,
            'provider_refund_id' => null,
        ]);
    }
}
