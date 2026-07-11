<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 99999),
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'currency' => 'EUR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 878,
            'total_amount' => 5499,
            'email' => fake()->safeEmail(),
            'placed_at' => now(),
        ];
    }
}
