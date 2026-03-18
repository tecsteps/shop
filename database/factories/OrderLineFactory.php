<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    protected $model = OrderLine::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'variant_id' => null,
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => strtoupper(fake()->bothify('???-####')),
            'variant_title_snapshot' => null,
            'price_amount' => 2500,
            'quantity' => 1,
            'total_amount' => 2500,
            'fulfilled_quantity' => 0,
            'requires_shipping' => true,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
