<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrderLine>
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
        $unitPrice = fake()->numberBetween(100, 10000);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'variant_id' => null,
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => fake()->bothify('SKU-####'),
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
