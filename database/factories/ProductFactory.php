<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => fake()->unique()->slug(2),
            'status' => ProductStatus::Draft,
            'description_html' => '<p>'.fake()->sentence().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->word(),
            'tags' => [],
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the product is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the product is active and published.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Archived,
        ]);
    }

    /**
     * Create variants (each with an inventory item) for the product.
     */
    public function withVariants(int $count = 1, array $attributes = []): static
    {
        return $this->afterCreating(function (\App\Models\Product $product) use ($count, $attributes): void {
            for ($i = 0; $i < $count; $i++) {
                $variant = $product->variants()->create(array_merge([
                    'price_amount' => fake()->numberBetween(100, 10000),
                    'position' => $i,
                    'is_default' => $i === 0,
                ], $attributes));

                $variant->inventoryItem()->create([
                    'store_id' => $product->store_id,
                    'quantity_on_hand' => 10,
                ]);
            }
        });
    }
}
