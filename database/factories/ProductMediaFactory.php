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
    protected $model = ProductMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => MediaType::Image->value,
            'storage_key' => 'products/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(),
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => 204800,
            'position' => 0,
            'status' => MediaStatus::Processing->value,
        ];
    }
}
