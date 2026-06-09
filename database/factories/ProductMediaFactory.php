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
            'type' => MediaType::Image,
            'storage_key' => 'media/'.Str::uuid().'.jpg',
            'alt_text' => null,
            'width' => null,
            'height' => null,
            'mime_type' => 'image/jpeg',
            'byte_size' => fake()->numberBetween(10_000, 500_000),
            'position' => 0,
            'status' => MediaStatus::Processing,
        ];
    }

    /**
     * Indicate that the media has finished processing.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MediaStatus::Ready,
            'width' => 1200,
            'height' => 800,
        ]);
    }
}
