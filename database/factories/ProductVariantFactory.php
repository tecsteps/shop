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
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'barcode' => fake()->optional()->ean13(),
            'price_amount' => fake()->numberBetween(500, 50000),
            'compare_at_amount' => null,
            'currency' => 'USD',
            'weight_g' => fake()->optional()->numberBetween(100, 5000),
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'compare_at_amount' => fake()->numberBetween(20000, 39999),
            'price_amount' => fake()->numberBetween(9999, 19999),
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_shipping' => false,
            'weight_g' => 0,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => VariantStatus::Archived,
        ]);
    }
}
