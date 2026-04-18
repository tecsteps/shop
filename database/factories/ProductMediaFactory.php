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

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => MediaType::Image,
            'storage_key' => 'products/'.$this->faker->uuid().'.jpg',
            'alt_text' => $this->faker->sentence(),
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => $this->faker->numberBetween(10_000, 500_000),
            'position' => 0,
            'status' => MediaStatus::Ready,
            'created_at' => now(),
        ];
    }
}
