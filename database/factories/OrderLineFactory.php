<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(100, 10000);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'variant_id' => ProductVariant::factory(),
            'title_snapshot' => fake()->sentence(3),
            'sku_snapshot' => 'SKU-'.fake()->regexify('[A-Z0-9]{6}'),
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'total_amount' => $quantity * $unitPrice,
            'tax_lines_json' => [['title' => 'VAT', 'rate' => 1900, 'amount' => fake()->numberBetween(0, 1000)]],
            'discount_allocations_json' => [],
        ];
    }
}
