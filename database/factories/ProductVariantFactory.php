<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'barcode' => null,
            'price_amount' => 2500,
            'compare_at_amount' => null,
            'currency' => 'USD',
            'weight_g' => 200,
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 0,
            'status' => 'active',
        ];
    }
}
