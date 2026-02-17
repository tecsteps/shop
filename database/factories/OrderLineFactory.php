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
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(500, 10000);
        $subtotal = $unitPrice * $quantity;

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'variant_id' => null,
            'title_snapshot' => fake()->words(3, true),
            'sku_snapshot' => strtoupper(fake()->bothify('??-####')),
            'variant_title_snapshot' => null,
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'subtotal_amount' => $subtotal,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'requires_shipping' => true,
        ];
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_shipping' => false,
        ]);
    }
}
