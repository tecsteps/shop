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
        $price = 1000;
        $qty = 1;

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
