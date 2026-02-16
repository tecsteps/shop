<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->bothify('???-####')),
            'barcode' => fake()->ean13(),
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
        return $this->state(['status' => VariantStatus::Archived]);
    }

    public function withComparePrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'compare_at_amount' => $attributes['price_amount'] + fake()->numberBetween(500, 5000),
        ]);
    }
}
