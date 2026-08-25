<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'mock',
            'method' => 'credit_card',
            'provider_payment_id' => 'mock_'.fake()->uuid(),
            'status' => 'captured',
            'amount' => 6543,
            'currency' => 'USD',
        ];
    }
}
