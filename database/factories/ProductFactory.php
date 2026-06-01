<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
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
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => Str::title($title),
            'handle' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'status' => ProductStatus::Draft->value,
            'description_html' => '<p>'.fake()->sentence().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Apparel', 'Accessories', 'Footwear', 'Home']),
            'tags' => fake()->randomElements(['summer', 'sale', 'new', 'featured'], 2),
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the product is active (published).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Active->value,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the product is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Archived->value,
        ]);
    }
}
