<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'title' => fake()->words(3, true), 'handle' => fake()->unique()->slug(3), 'description' => fake()->paragraph(), 'vendor' => fake()->company(), 'product_type' => 'Apparel', 'tags' => ['featured'], 'status' => ProductStatus::Active, 'published_at' => now(), 'sales_count' => 0];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProductStatus::Draft, 'published_at' => null]);
    }
}
