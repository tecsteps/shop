<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'product_id' => Product::factory(),
            'sku' => strtoupper(Str::random(3)).'-'.fake()->unique()->numberBetween(1000, 999999),
            'barcode' => null,
            'price_amount' => fake()->numberBetween(500, 50000),
            'compare_at_amount' => null,
            'currency' => 'USD',
            'weight_g' => fake()->numberBetween(50, 2000),
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active->value,
        ];
    }

    /**
     * Indicate that the variant is the product's default variant.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the variant is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VariantStatus::Archived->value,
        ]);
    }
}
