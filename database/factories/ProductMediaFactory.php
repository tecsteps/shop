<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductMedia> */
class ProductMediaFactory extends Factory
{
    protected $model = ProductMedia::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => MediaType::Image,
            'storage_key' => 'products/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(3),
            'width' => 800,
            'height' => 600,
            'mime_type' => 'image/jpeg',
            'byte_size' => fake()->numberBetween(10000, 500000),
            'position' => 0,
            'status' => MediaStatus::Ready,
            'created_at' => now(),
        ];
    }

    public function processing(): static
    {
        return $this->state(['status' => MediaStatus::Processing]);
    }

    public function video(): static
    {
        return $this->state([
            'type' => MediaType::Video,
            'storage_key' => 'products/'.fake()->uuid().'.mp4',
            'mime_type' => 'video/mp4',
        ]);
    }
}
