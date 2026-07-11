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
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => MediaType::Image,
            'storage_key' => 'media/originals/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(4),
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => fake()->numberBetween(10000, 5000000),
            'position' => 0,
            'status' => MediaStatus::Processing,
        ];
    }
}
