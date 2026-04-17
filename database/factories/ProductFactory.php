<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
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
            'title' => ucfirst($title),
            'handle' => Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'status' => ProductStatus::Active->value,
            'description_html' => '<p>'.fake()->paragraph().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Apparel', 'Footwear', 'Accessories', 'Home']),
            'tags' => [fake()->word(), fake()->word()],
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Archived->value,
        ]);
    }
}
