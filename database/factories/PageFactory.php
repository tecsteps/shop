<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
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
            'body_html' => fake()->paragraph(),
            'status' => fake()->randomElement(PageStatus::cases()),
            'published_at' => fake()->dateTime(),
        ];
    }
}
