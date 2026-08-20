<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    protected $model = \App\Models\Collection::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'title' => fake()->words(2, true), 'handle' => fake()->unique()->slug(2), 'description' => fake()->paragraph(), 'status' => 'active', 'image_url' => null];
    }
}
