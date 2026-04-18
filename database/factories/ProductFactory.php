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
        $title = $this->faker->unique()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucwords($title),
            'handle' => Str::slug($title.'-'.$this->faker->unique()->numberBetween(1000, 99999)),
            'status' => ProductStatus::Draft,
            'description_html' => '<p>'.$this->faker->paragraph().'</p>',
            'vendor' => $this->faker->company(),
            'product_type' => $this->faker->randomElement(['Apparel', 'Accessories', 'Footwear']),
            'tags' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => ProductStatus::Active, 'published_at' => now()]);
    }

    public function archived(): static
    {
        return $this->state(['status' => ProductStatus::Archived]);
    }
}
