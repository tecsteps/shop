<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartLine>
 */
class CartLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(500, 20000);
        $quantity = fake()->numberBetween(1, 4);

        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => $quantity,
            'unit_price_amount' => $price,
            'line_subtotal_amount' => $price * $quantity,
            'line_discount_amount' => 0,
            'line_total_amount' => $price * $quantity,
        ];
    }
}
