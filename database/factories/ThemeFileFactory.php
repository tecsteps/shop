<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ThemeFile>
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
        $path = 'templates/'.fake()->unique()->slug(1).'.blade.php';

        return [
            'theme_id' => Theme::factory(),
            'path' => $path,
            'storage_key' => 'themes/'.fake()->uuid().'/'.$path,
            'sha256' => hash('sha256', fake()->sentence()),
            'byte_size' => fake()->numberBetween(100, 50000),
        ];
    }
}
