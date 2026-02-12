<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CartLine> */
class CartLineFactory extends Factory
{
    protected $model = CartLine::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(999, 19999);
        $subtotal = $unitPrice * $quantity;

        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'product_title' => fake()->words(3, true),
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ];
    }
}
