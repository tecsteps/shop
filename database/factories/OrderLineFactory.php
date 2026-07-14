<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'variant_id' => ProductVariant::factory(),
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => fake()->bothify('SKU-####-???'),
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price_amount' => fake()->numberBetween(999, 19999),
            'total_amount' => fn (array $attributes): int => $attributes['unit_price_amount'] * $attributes['quantity'],
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
