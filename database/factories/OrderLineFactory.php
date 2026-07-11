<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderLine>
 */
class OrderLineFactory extends Factory
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
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => fake()->unique()->bothify('SKU-####'),
            'quantity' => 1,
            'unit_price_amount' => 2500,
            'total_amount' => 2500,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
