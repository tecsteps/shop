<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = implode(' ', fake()->words(3));

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'status' => ProductStatus::Active,
            'description_html' => '<p>'.fake()->paragraph().'</p><p>'.fake()->paragraph().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Shirts', 'Pants', 'Shoes', 'Accessories', 'Electronics', 'Books']),
            'tags' => fake()->randomElements(['new', 'sale', 'trending', 'popular', 'limited'], fake()->numberBetween(1, 3)),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Archived,
        ]);
    }

    public function withVariants(int $count = 3): static
    {
        return $this->afterCreating(function (Product $product) use ($count): void {
            ProductVariant::factory()->count($count)->create([
                'product_id' => $product->id,
            ]);
        });
    }

    public function withDefaultVariant(int $price = 1999): static
    {
        return $this->afterCreating(function (Product $product) use ($price): void {
            ProductVariant::factory()->default()->create([
                'product_id' => $product->id,
                'price_amount' => $price,
                'currency' => 'EUR',
            ]);
        });
    }
}
