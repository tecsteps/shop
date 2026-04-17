<?php

namespace Database\Factories;

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
            'name' => fake()->company().' Integration',
            'status' => 'active',
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disabled',
        ]);
    }
}
