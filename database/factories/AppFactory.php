<?php

namespace Database\Factories;

use App\Enums\AppStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\App>
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
            'name' => $this->faker->company().' '.$this->faker->randomElement(['Sync', 'Connect', 'Plugin', 'Integration']),
            'status' => AppStatus::Active,
            'created_at' => $this->faker->dateTimeBetween('-6 months'),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppStatus::Disabled,
        ]);
    }
}
