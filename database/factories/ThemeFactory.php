<?php

namespace Database\Factories;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(2, true).' Theme',
            'version' => '1.0.0',
            'status' => ThemeStatus::Draft,
        ];
    }

    /**
     * Indicate that the theme is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThemeStatus::Published,
            'published_at' => now()->toIso8601String(),
        ]);
    }
}
