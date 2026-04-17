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
    protected $model = Refund::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'amount' => fake()->numberBetween(500, 10000),
            'currency' => 'EUR',
            'status' => RefundStatus::Pending,
            'reason' => 'Customer requested refund',
            'restock' => false,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_refund_'.fake()->uuid(),
            'processed_at' => now(),
        ]);
    }
}
