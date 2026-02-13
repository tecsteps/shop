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
    /**
     * Define the model's default state.
     *
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
            'weight_g' => fake()->numberBetween(100, 5000),
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 0,
            'status' => VariantStatus::Active,
        ];
    }

    /**
     * Indicate that this is not the default variant.
     */
    public function nonDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => false,
        ]);
    }

    /**
     * Indicate that this variant is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VariantStatus::Archived,
        ]);
    }

    /**
     * Indicate that this variant has a compare-at price (on sale).
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'compare_at_amount' => ($attributes['price_amount'] ?? 2000) + fake()->numberBetween(500, 5000),
        ]);
    }

    /**
     * Indicate that this variant is a digital product.
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_shipping' => false,
            'weight_g' => null,
        ]);
    }
}
