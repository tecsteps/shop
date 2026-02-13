<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThemeFile>
 */
class ThemeFileFactory extends Factory
{
    protected $model = ThemeFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'path' => 'templates/'.fake()->word().'.liquid',
            'storage_key' => fake()->uuid(),
            'sha256' => hash('sha256', fake()->text()),
            'byte_size' => fake()->numberBetween(100, 50000),
        ];
    }
}
