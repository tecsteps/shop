<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price_amount' => fake()->numberBetween(999, 19999),
            'line_subtotal_amount' => fn (array $attributes): int => $attributes['unit_price_amount'] * $attributes['quantity'],
            'line_discount_amount' => 0,
            'line_total_amount' => fn (array $attributes): int => $attributes['line_subtotal_amount'] - $attributes['line_discount_amount'],
        ];
    }
}
