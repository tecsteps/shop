<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
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
        $payment = Payment::factory()->create();

        return [
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
            'amount' => fake()->numberBetween(100, $payment->amount),
            'reason' => fake()->optional()->sentence(),
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_ref_'.Str::random(16),
        ];
    }
}
