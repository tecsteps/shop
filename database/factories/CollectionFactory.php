<?php

namespace Database\Factories;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'title' => fake()->sentence(3),
            'handle' => fake()->unique()->slug(),
            'description_html' => fake()->paragraph(),
            'type' => fake()->randomElement(CollectionType::cases()),
            'status' => fake()->randomElement(CollectionStatus::cases()),
        ];
    }
}
