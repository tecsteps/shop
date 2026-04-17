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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => MediaType::Image->value,
            'storage_key' => 'media/originals/'.Str::random(24).'.jpg',
            'alt_text' => fake()->sentence(4),
            'width' => 1200,
            'height' => 1200,
            'mime_type' => 'image/jpeg',
            'byte_size' => fake()->numberBetween(50_000, 500_000),
            'position' => 0,
            'status' => MediaStatus::Ready->value,
            'created_at' => now(),
        ];
    }

    public function processing(): self
    {
        return $this->state(fn (): array => ['status' => MediaStatus::Processing->value]);
    }
}
