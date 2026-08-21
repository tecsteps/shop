<?php

namespace Database\Factories;

use App\Models\CartLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartLine>
 */
class CartLineFactory extends Factory
{
    protected $model = CartLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => CartFactory::new(),
            'variant_id' => ProductVariantFactory::new(),
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price_amount' => fake()->numberBetween(999, 19999),
            'line_subtotal_amount' => 0,
            'line_discount_amount' => 0,
            'line_total_amount' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CartLine $line): void {
            $line->line_subtotal_amount = $line->unit_price_amount * $line->quantity;
            $line->line_total_amount = $line->line_subtotal_amount - $line->line_discount_amount;
        });
    }
}
