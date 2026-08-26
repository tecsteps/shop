<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartLine>
 */
class CartLineFactory extends Factory
{
    protected $model = CartLine::class;

    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 5);
        $price = fake()->numberBetween(100, 10000);

        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => $qty,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price * $qty,
            'line_discount_amount' => 0,
            'line_total_amount' => $price * $qty,
        ];
    }
}
