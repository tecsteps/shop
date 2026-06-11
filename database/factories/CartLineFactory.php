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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(999, 19999);

        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $unitPrice * $quantity,
            'line_discount_amount' => 0,
            'line_total_amount' => $unitPrice * $quantity,
        ];
    }

    /**
     * Set an explicit quantity and unit price with consistent derived amounts.
     */
    public function priced(int $unitPriceAmount, int $quantity = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
            'unit_price_amount' => $unitPriceAmount,
            'line_subtotal_amount' => $unitPriceAmount * $quantity,
            'line_discount_amount' => 0,
            'line_total_amount' => $unitPriceAmount * $quantity,
        ]);
    }
}
