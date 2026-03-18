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

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->optional()->bothify('???-###'),
            'barcode' => null,
            'price_amount' => fake()->numberBetween(500, 50000),
            'compare_at_amount' => null,
            'currency' => 'EUR',
            'weight_g' => fake()->numberBetween(100, 5000),
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function onSale(int $compareAtAmount = 4999): static
    {
        return $this->state(fn (array $attributes) => [
            'compare_at_amount' => $compareAtAmount,
        ]);
    }
}
