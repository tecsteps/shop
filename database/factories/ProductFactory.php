<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => Str::title($title),
            'handle' => Str::slug($title),
            'status' => 'active',
            'description_html' => '<p>'.fake()->paragraph().'</p><p>'.fake()->paragraph().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Shirts', 'Pants', 'Shoes', 'Accessories', 'Electronics', 'Books']),
            'tags' => fake()->randomElements(['new', 'sale', 'trending', 'popular', 'limited'], fake()->numberBetween(1, 3)),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'draft', 'published_at' => null]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'archived']);
    }

    public function withVariants(int $count = 1): static
    {
        return $this->afterCreating(function (Product $product) use ($count): void {
            ProductVariant::factory()->count($count)->for($product)->create();
        });
    }

    public function withDefaultVariant(int $price = 1000): static
    {
        return $this->afterCreating(function (Product $product) use ($price): void {
            ProductVariant::factory()->default()->for($product)->create(['price_amount' => $price]);
        });
    }
}
