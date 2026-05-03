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
        $contents = fake()->sentence();

        return [
            'theme_id' => Theme::factory(),
            'path' => 'templates/'.fake()->unique()->slug().'.blade.php',
            'storage_key' => 'themes/'.fake()->uuid().'.blade.php',
            'sha256' => hash('sha256', $contents),
            'byte_size' => strlen($contents),
        ];
    }
}
