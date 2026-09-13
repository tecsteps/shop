<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'title' => fake()->sentence(3),
            'handle' => fake()->unique()->slug(),
            'status' => fake()->randomElement(ProductStatus::cases()),
            'description_html' => fake()->paragraph(),
            'vendor' => fake()->company(),
            'product_type' => fake()->word(),
            'tags' => [fake()->word()],
            'published_at' => fake()->dateTime(),
        ];
    }
}
