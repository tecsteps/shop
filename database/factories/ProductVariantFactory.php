<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use App\Models\Product;
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
            'product_id' => Product::factory(),
            'sku' => null,
            'barcode' => null,
            'price_amount' => fake()->numberBetween(500, 50000),
            'compare_at_amount' => null,
            'currency' => 'EUR',
            'weight_g' => fake()->numberBetween(50, 2000),
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active,
        ];
    }

    /**
     * Indicate that the variant is the product's default variant.
     */
    public function asDefault(): static
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
            'status' => VariantStatus::Archived,
        ]);
    }

    /**
     * Set an explicit price in minor units.
     */
    public function priced(int $priceAmount): static
    {
        return $this->state(fn (array $attributes) => [
            'price_amount' => $priceAmount,
        ]);
    }
}
