<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucfirst($title),
            'handle' => Str::slug($title).'-'.Str::random(4),
            'status' => ProductStatus::Draft->value,
            'description_html' => '<p>'.fake()->paragraph().'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Apparel', 'Footwear', 'Accessories', 'Home']),
            'tags' => [],
            'published_at' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Active->value,
            'published_at' => now(),
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Archived->value,
        ]);
    }
}
