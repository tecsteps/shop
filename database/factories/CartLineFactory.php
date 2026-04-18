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
        $quantity = $this->faker->numberBetween(1, 3);
        $unit = $this->faker->numberBetween(500, 5000);

        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => $quantity,
            'unit_price_amount' => $unit,
            'line_subtotal_amount' => $unit * $quantity,
            'line_discount_amount' => 0,
            'line_total_amount' => $unit * $quantity,
        ];
    }
}
