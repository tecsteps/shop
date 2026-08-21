<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition(): array
    {
        $description = collect(fake()->paragraphs(2))->map(fn (string $paragraph): string => "<p>{$paragraph}</p>")->implode('');

        return [
            'store_id' => Store::factory(),
            'title' => fake()->words(3, true),
            'handle' => fake()->unique()->slug(3),
            'description' => strip_tags($description),
            'description_html' => $description,
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Shirts', 'Pants', 'Shoes', 'Accessories', 'Electronics', 'Books']),
            'tags' => fake()->randomElements(['new', 'sale', 'trending', 'popular', 'limited'], fake()->numberBetween(1, 3)),
            'status' => ProductStatus::Active,
            'published_at' => now(),
            'sales_count' => 0,
            'metadata' => [],
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft, 'published_at' => null]);
    }

    public function archived(): static
    {
        return $this->state(['status' => ProductStatus::Archived]);
    }

    public function withVariants(int $count): static
    {
        return $this->afterCreating(function (Product $product) use ($count): void {
            ProductVariantFactory::new()->count($count)->for($product)->create();
        });
    }

    public function withDefaultVariant(int $priceAmount): static
    {
        return $this->afterCreating(function (Product $product) use ($priceAmount): void {
            ProductVariantFactory::new()->for($product)->state([
                'price_amount' => $priceAmount,
                'is_default' => true,
            ])->create();
        });
    }
}
