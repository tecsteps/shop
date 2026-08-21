<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => OrderFactory::new(),
            'payment_id' => PaymentFactory::new(),
            'amount' => fake()->numberBetween(999, 19999),
            'reason' => fake()->sentence(),
            'status' => RefundStatus::Processed,
            'restock' => false,
            'provider_refund_id' => 'mock_re_'.fake()->unique()->bothify('????????????????????'),
            'lines_json' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => RefundStatus::Pending, 'provider_refund_id' => null]);
    }

    public function failed(): static
    {
        return $this->state(['status' => RefundStatus::Failed]);
    }
}
