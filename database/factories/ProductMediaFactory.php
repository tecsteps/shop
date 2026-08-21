<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\ProductMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductMedia>
 */
class ProductMediaFactory extends Factory
{
    protected $model = ProductMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'type' => MediaType::Image,
            'path' => 'products/'.fake()->uuid().'.jpg',
            'storage_key' => null,
            'url' => 'https://images.example.test/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(),
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => 100000,
            'checksum' => fake()->sha256(),
            'status' => MediaStatus::Ready,
            'position' => 0,
            'metadata' => [],
        ];
    }
}
