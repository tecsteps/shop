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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->numberBetween(1000, 10000);

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'variant_id' => null,
            'title_snapshot' => fake()->words(3, true),
            'variant_title_snapshot' => fake()->word(),
            'sku_snapshot' => strtoupper(fake()->bothify('??-###')),
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'subtotal_amount' => $unitPrice * $quantity,
            'total_amount' => $unitPrice * $quantity,
            'requires_shipping' => true,
        ];
    }
}
