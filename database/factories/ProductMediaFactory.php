<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductMedia>
 */
class ProductMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => MediaType::Image->value,
            'storage_key' => 'products/'.Str::uuid().'.jpg',
            'alt_text' => fake()->sentence(3),
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => fake()->numberBetween(10000, 500000),
            'position' => 0,
            'status' => MediaStatus::Ready->value,
        ];
    }

    /**
     * Indicate that the media is still processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MediaStatus::Processing->value,
        ]);
    }
}
