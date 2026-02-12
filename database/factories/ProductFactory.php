<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->words(3, true));

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => ProductStatus::Active,
            'description_html' => '<p>'.fake()->paragraph().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Shirts', 'Pants', 'Shoes', 'Accessories', 'Electronics', 'Books']),
            'tags' => fake()->randomElements(['new', 'sale', 'trending', 'popular', 'limited'], fake()->numberBetween(1, 3)),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Archived,
        ]);
    }

    public function withVariants(int $count = 1): static
    {
        return $this->afterCreating(function (Product $product) use ($count): void {
            ProductVariant::factory()
                ->count($count)
                ->for($product)
                ->create();
        });
    }

    public function withDefaultVariant(int $price = 1999): static
    {
        return $this->afterCreating(function (Product $product) use ($price): void {
            ProductVariant::factory()
                ->for($product)
                ->default()
                ->state(['price_amount' => $price, 'currency' => 'EUR'])
                ->create();
        });
    }
}
