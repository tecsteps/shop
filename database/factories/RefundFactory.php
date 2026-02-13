<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'amount' => fake()->numberBetween(100, 50000),
            'reason' => fake()->optional()->sentence(),
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_ref_'.Str::random(16),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RefundStatus::Pending,
            'provider_refund_id' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RefundStatus::Failed,
        ]);
    }
}
