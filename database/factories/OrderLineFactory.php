<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderLine>
 */
class OrderLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(500, 20000);
        $quantity = fake()->numberBetween(1, 4);

        return [
            'order_id' => Order::factory(),
            'store_id' => null,
            'product_id' => null,
            'variant_id' => ProductVariant::factory(),
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => strtoupper(fake()->bothify('???-####')),
            'quantity' => $quantity,
            'unit_price_amount' => $price,
            'total_amount' => $price * $quantity,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
