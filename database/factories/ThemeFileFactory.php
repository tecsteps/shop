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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'path' => 'templates/'.fake()->unique()->word().'.blade.php',
            'storage_key' => 'themes/'.Str::random(16),
            'sha256' => hash('sha256', Str::random(32)),
            'byte_size' => fake()->numberBetween(100, 10000),
        ];
    }
}
