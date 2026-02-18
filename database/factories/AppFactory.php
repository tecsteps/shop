<?php

namespace Database\Factories;

use App\Enums\AppStatus;
use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<App>
 */
class AppFactory extends Factory
{
    protected $model = App::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' App',
            'status' => AppStatus::Active,
            'created_at' => now(),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppStatus::Disabled,
        ]);
    }
}
