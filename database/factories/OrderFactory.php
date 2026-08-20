<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = \App\Models\Order::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'order_number' => '#'.fake()->unique()->numberBetween(1001, 9999), 'currency' => 'EUR', 'status' => 'processing', 'financial_status' => 'paid', 'fulfillment_status' => 'unfulfilled', 'email' => fake()->safeEmail(), 'subtotal_amount' => 2499, 'discount_amount' => 0, 'shipping_amount' => 499, 'tax_amount' => 0, 'total_amount' => 2998, 'placed_at' => now()];
    }
}
