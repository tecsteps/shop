<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderLine> */
class OrderLineFactory extends Factory
{
    protected $model = OrderLine::class;

    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 3);
        $price = fake()->numberBetween(1000, 10000);

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'variant_id' => null,
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => strtoupper(fake()->lexify('???-####')),
            'variant_title_snapshot' => null,
            'quantity' => $qty,
            'unit_price' => $price,
            'subtotal' => $qty * $price,
            'total' => $qty * $price,
            'requires_shipping' => true,
        ];
    }
}
