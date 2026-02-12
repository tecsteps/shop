<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    protected $model = OrderLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 3);
        $unit = fake()->numberBetween(999, 19999);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'variant_id' => ProductVariant::factory(),
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => 'SKU-'.fake()->numerify('####'),
            'quantity' => $qty,
            'unit_price_amount' => $unit,
            'total_amount' => $qty * $unit,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
