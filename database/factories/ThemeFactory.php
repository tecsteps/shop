<?php

namespace Database\Factories;

use App\Enums\ThemeStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(2, true),
            'version' => fake()->randomElement(['1.0.0', '1.1.0', '2.0.0']),
            'status' => fake()->randomElement(ThemeStatus::cases()),
            'published_at' => fake()->dateTime(),
        ];
    }
}
