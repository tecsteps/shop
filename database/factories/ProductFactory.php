<?php

namespace Database\Factories;

use App\Models\Product;
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
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title),
            'status' => 'draft',
            'description_html' => '<p>'.fake()->paragraph().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Apparel', 'Electronics', 'Home', 'Sports']),
            'tags' => [fake()->word(), fake()->word()],
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'published_at' => now()->toIso8601String(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
