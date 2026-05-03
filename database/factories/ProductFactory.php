<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        $title = Str::title(fake()->words(3, true));

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'status' => ProductStatus::Active,
            'description_html' => '<p>'.fake()->paragraphs(2, true).'</p>',
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

    public function withVariants(int $count = 1): static
    {
        return $this->afterCreating(function ($product) use ($count): void {
            ProductVariant::factory()
                ->count($count)
                ->for($product)
                ->create(['currency' => $product->store->default_currency]);
        });
    }

    public function withDefaultVariant(int $price = 2499): static
    {
        return $this->afterCreating(function ($product) use ($price): void {
            ProductVariant::factory()
                ->default()
                ->for($product)
                ->create([
                    'price_amount' => $price,
                    'currency' => $product->store->default_currency,
                ]);
        });
    }
}
