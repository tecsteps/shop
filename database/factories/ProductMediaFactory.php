<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => fake()->randomElement(MediaType::cases()),
            'storage_key' => 'products/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(4),
            'width' => fake()->numberBetween(100, 2000),
            'height' => fake()->numberBetween(100, 2000),
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'video/mp4']),
            'byte_size' => fake()->numberBetween(1000, 5000000),
            'position' => fake()->numberBetween(0, 10),
            'status' => fake()->randomElement(MediaStatus::cases()),
        ];
    }
}
