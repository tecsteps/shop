<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
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
        $unitPrice = 2500;
        $quantity = 2;

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'variant_id' => ProductVariant::factory(),
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => fake()->bothify('SKU-####'),
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'total_amount' => $unitPrice * $quantity,
            'tax_lines_json' => null,
            'discount_allocations_json' => null,
        ];
    }
}
