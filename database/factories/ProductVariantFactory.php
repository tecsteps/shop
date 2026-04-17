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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(Str::random(8)),
            'barcode' => null,
            'price_amount' => fake()->numberBetween(500, 50000),
            'compare_at_amount' => null,
            'currency' => 'USD',
            'weight_g' => fake()->numberBetween(50, 2000),
            'requires_shipping' => 1,
            'is_default' => 0,
            'position' => 0,
            'status' => VariantStatus::Active->value,
        ];
    }

    public function default(): self
    {
        return $this->state(fn (): array => ['is_default' => 1]);
    }

    public function archived(): self
    {
        return $this->state(fn (): array => ['status' => VariantStatus::Archived->value]);
    }
}
