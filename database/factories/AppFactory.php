<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\App>
 */
class AppFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' App',
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the app is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disabled',
        ]);
    }
}
