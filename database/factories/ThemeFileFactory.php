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
        $path = 'templates/'.fake()->unique()->slug(2).'.blade.php';
        $contents = fake()->paragraph();

        return [
            'theme_id' => Theme::factory(),
            'path' => $path,
            'storage_key' => 'themes/'.$path,
            'sha256' => hash('sha256', $contents),
            'byte_size' => strlen($contents),
        ];
    }
}
