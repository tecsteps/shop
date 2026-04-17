<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucwords($title),
            'handle' => Str::slug($title),
            'status' => ProductStatus::Active->value,
            'description_html' => '<p>'.fake()->paragraph(3).'</p>',
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Shirt', 'Pants', 'Shoes', 'Accessory']),
            'tags' => json_encode(fake()->randomElements(['summer', 'sale', 'new', 'featured'], 2)),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ProductStatus::Draft->value,
            'published_at' => null,
        ]);
    }
}
