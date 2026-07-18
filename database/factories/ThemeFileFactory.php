<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThemeFile>
 */
class ThemeFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'path' => 'templates/'.fake()->unique()->word().'.blade.php',
            'storage_key' => 'themes/'.fake()->uuid(),
            'sha256' => hash('sha256', fake()->uuid()),
            'byte_size' => fake()->numberBetween(100, 10000),
        ];
    }
}
