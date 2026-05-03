<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
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
        $store = Store::factory()->create();
        $cart = Cart::factory()->for($store)->create();
        $product = Product::factory()->for($store)->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'price_amount' => fake()->numberBetween(1000, 10000),
            'currency' => $cart->currency,
        ]);
        $quantity = fake()->numberBetween(1, 4);
        $subtotal = $variant->price_amount * $quantity;

        return [
            'cart_id' => $cart->id,
            'variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price_amount' => $variant->price_amount,
            'line_subtotal_amount' => $subtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $subtotal,
        ];
    }
}
