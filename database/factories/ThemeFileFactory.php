<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'path' => 'templates/'.fake()->unique()->slug().'.blade.php',
            'storage_key' => 'themes/'.Str::uuid().'.blade.php',
            'sha256' => hash('sha256', fake()->text()),
            'byte_size' => fake()->numberBetween(100, 100000),
        ];
    }
}
