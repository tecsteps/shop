<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => $title,
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
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    public function withVariants(int $count = 3): static
    {
        return $this->afterCreating(function (Product $product) use ($count) {
            ProductVariant::factory()->count($count)->create([
                'product_id' => $product->id,
            ]);
        });
    }

    public function withDefaultVariant(int $price = 2499): static
    {
        return $this->afterCreating(function (Product $product) use ($price) {
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'price_amount' => $price,
                'is_default' => true,
            ]);
        });
    }
}
