<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'price_amount' => fake()->numberBetween(500, 10000),
            'compare_at_amount' => null,
            'currency' => 'EUR',
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 0,
            'status' => 'active',
        ];
    }
}
