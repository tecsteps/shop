<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'SKU-'.fake()->unique()->regexify('[A-Z0-9]{6}'),
            'barcode' => fake()->ean13(),
            'price_amount' => fake()->numberBetween(100, 50000),
            'compare_at_amount' => fake()->numberBetween(100, 60000),
            'currency' => 'USD',
            'weight_g' => fake()->numberBetween(10, 5000),
            'requires_shipping' => true,
            'is_default' => false,
            'position' => fake()->numberBetween(0, 10),
            'status' => fake()->randomElement(VariantStatus::cases()),
        ];
    }
}
