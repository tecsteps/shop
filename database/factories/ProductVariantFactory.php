<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductVariant>
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
            'sku' => fake()->unique()->bothify('SKU-####'),
            'barcode' => null,
            'price_amount' => fake()->numberBetween(100, 10000),
            'compare_at_amount' => null,
            'currency' => 'USD',
            'weight_g' => null,
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active,
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
            'status' => VariantStatus::Archived,
        ]);
    }

    /**
     * Create an inventory item for the variant.
     */
    public function withInventory(int $quantityOnHand = 10): static
    {
        return $this->afterCreating(function (\App\Models\ProductVariant $variant) use ($quantityOnHand): void {
            $variant->inventoryItem()->create([
                'store_id' => $variant->product->store_id,
                'quantity_on_hand' => $quantityOnHand,
            ]);
        });
    }
}
