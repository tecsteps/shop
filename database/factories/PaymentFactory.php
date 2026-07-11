<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
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
            'provider' => 'mock',
            'method' => 'credit_card',
            'provider_payment_id' => 'mock_'.fake()->uuid(),
            'status' => 'captured',
            'amount' => 5499,
            'currency' => 'EUR',
            'raw_json_encrypted' => ['provider' => 'mock'],
        ];
    }
}
