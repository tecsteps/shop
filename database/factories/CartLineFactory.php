<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CartLine>
 */
class CartLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => 1,
            'unit_price_amount' => fake()->numberBetween(100, 10000),
            'line_subtotal_amount' => 0,
            'line_discount_amount' => 0,
            'line_total_amount' => 0,
        ];
    }

    /**
     * Recalculate derived amounts after creation.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (CartLine $line): void {
            $line->line_subtotal_amount = $line->unit_price_amount * $line->quantity;
            $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
        });
    }
}
