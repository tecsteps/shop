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

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'amount' => 2500,
            'reason' => 'Customer request',
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_refund_'.fake()->uuid(),
            'created_at' => now(),
        ];
    }
}
