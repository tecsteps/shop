<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class NavigationMenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'handle' => fake()->unique()->slug(2),
            'title' => fake()->words(2, true),
        ];
    }
}
