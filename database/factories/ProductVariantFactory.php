<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = \App\Models\ProductVariant::class;

    public function definition(): array
    {
        $weight = fake()->numberBetween(100, 5000);

        return [
            'product_id' => ProductFactory::new(),
            'title' => 'Default',
            'sku' => strtoupper(fake()->bothify('SKU-####-???')),
            'barcode' => fake()->ean13(),
            'price_amount' => fake()->numberBetween(999, 19999),
            'compare_at_amount' => null,
            'cost_amount' => null,
            'currency' => 'EUR',
            'weight_grams' => $weight,
            'weight_g' => $weight,
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active,
            'metadata' => [],
        ];
    }

    public function onSale(): static
    {
        return $this->state([
            'compare_at_amount' => fake()->numberBetween(20000, 39999),
            'price_amount' => fake()->numberBetween(9999, 19999),
        ]);
    }

    public function digital(): static
    {
        return $this->state(['requires_shipping' => false, 'weight_grams' => 0, 'weight_g' => 0]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function archived(): static
    {
        return $this->state(['status' => VariantStatus::Archived]);
    }
}
