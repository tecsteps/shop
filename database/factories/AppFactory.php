<?php

namespace Database\Factories;

use App\Enums\AppStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'status' => fake()->randomElement(AppStatus::cases()),
        ];
    }
}
