<?php

namespace Database\Factories;

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
            'type' => 'image',
            'storage_key' => 'products/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(3),
            'width' => 1200,
            'height' => 800,
            'mime_type' => 'image/jpeg',
            'byte_size' => fake()->numberBetween(50000, 500000),
            'position' => 0,
            'status' => 'ready',
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'width' => null,
            'height' => null,
            'mime_type' => null,
            'byte_size' => null,
        ]);
    }
}
