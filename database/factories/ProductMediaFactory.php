<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductMedia>
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
            'storage_key' => 'media/originals/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(),
            'position' => 0,
            'status' => MediaStatus::Processing,
        ];
    }

    /**
     * Indicate that the media finished processing.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MediaStatus::Ready,
        ]);
    }
}
