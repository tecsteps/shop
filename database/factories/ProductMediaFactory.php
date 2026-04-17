<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductMedia>
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
            'storage_key' => 'media/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(3),
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => fake()->numberBetween(50000, 500000),
            'position' => 0,
            'status' => MediaStatus::Ready,
        ];
    }

    /**
     * Indicate that the media is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MediaStatus::Processing,
        ]);
    }

    /**
     * Indicate that the media processing failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MediaStatus::Failed,
        ]);
    }

    /**
     * Indicate that the media is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MediaType::Video,
            'mime_type' => 'video/mp4',
            'storage_key' => 'media/'.fake()->uuid().'.mp4',
        ]);
    }
}
