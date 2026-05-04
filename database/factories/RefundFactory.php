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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'amount' => fake()->numberBetween(500, 5000),
            'reason' => fake()->optional()->sentence(),
            'status' => RefundStatus::Pending,
            'provider_refund_id' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_refund_'.fake()->unique()->lexify('????????????????'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RefundStatus::Failed,
        ]);
    }

    public function forPayment(Payment $payment): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $payment->order_id,
            'payment_id' => $payment->getKey(),
        ]);
    }
}
