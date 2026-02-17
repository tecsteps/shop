<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ThemeFile>
 */
class ThemeFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $path = 'templates/'.fake()->word().'.blade.php';

        return [
            'theme_id' => Theme::factory(),
            'path' => $path,
            'storage_key' => 'themes/'.Str::random(40),
            'sha256' => hash('sha256', fake()->text()),
            'byte_size' => fake()->numberBetween(100, 50000),
        ];
    }
}
