<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-????-####'),
            'barcode' => null,
            'price_amount' => fake()->numberBetween(500, 50000),
            'compare_at_amount' => null,
            'currency' => 'USD',
            'weight_g' => fake()->numberBetween(100, 5000),
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VariantStatus::Archived,
        ]);
    }
}
