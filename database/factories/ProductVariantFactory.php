<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('SKU-####-???'),
            'barcode' => fake()->ean13(),
            'price_amount' => fake()->numberBetween(999, 19999),
            'compare_at_amount' => null,
            'currency' => 'EUR',
            'weight_g' => fake()->numberBetween(100, 5000),
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => 'active',
        ];
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'price_amount' => fake()->numberBetween(9999, 19999),
            'compare_at_amount' => fake()->numberBetween(20000, 39999),
        ]);
    }

    public function digital(): static
    {
        return $this->state(fn (array $attributes) => ['requires_shipping' => false, 'weight_g' => 0]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'archived']);
    }
}
