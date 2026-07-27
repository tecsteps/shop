<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Refund>
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
            'order_id' => fn (array $attributes) => Payment::find($attributes['payment_id'])?->order_id,
            'payment_id' => Payment::factory(),
            'amount' => 1000,
            'reason' => null,
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_refund_'.Str::random(16),
        ];
    }
}
