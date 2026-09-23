<?php

namespace Database\Factories;

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
        return [
            'store_id' => \App\Models\Store::factory(),
            'title' => fake()->words(3, true),
            'handle' => fn (array $attributes): string => Str::slug($attributes['title']).'-'.fake()->unique()->numberBetween(1, 100000),
            'status' => 'active',
            'description_html' => fake()->paragraph(),
            'vendor' => fake()->company(),
            'product_type' => 'General',
            'tags' => ['test'],
            'published_at' => now(),
        ];
    }
}
