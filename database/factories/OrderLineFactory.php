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
            'title_snapshot' => $this->faker->words(3, true),
            'sku_snapshot' => strtoupper($this->faker->bothify('SKU-####')),
            'quantity' => 1,
            'unit_price_amount' => 1000,
            'total_amount' => 1000,
            'tax_lines_json' => [],
            'discount_allocations_json' => [],
        ];
    }
}
