<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = \App\Models\ProductVariant::class;

    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'title' => 'Default', 'sku' => strtoupper(fake()->bothify('SKU-####')), 'price_amount' => 2499, 'compare_at_amount' => null, 'cost_amount' => 1000, 'weight_grams' => 250, 'requires_shipping' => true, 'is_default' => true, 'position' => 0];
    }
}
