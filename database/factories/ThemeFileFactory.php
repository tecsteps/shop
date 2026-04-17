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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extensions = ['blade.php', 'css', 'js', 'json'];
        $directories = ['layouts', 'sections', 'snippets', 'assets'];

        return [
            'theme_id' => Theme::factory(),
            'path' => fake()->randomElement($directories).'/'.fake()->word().'.'.fake()->randomElement($extensions),
            'storage_key' => 'themes/'.fake()->uuid(),
            'sha256' => hash('sha256', fake()->text()),
            'byte_size' => fake()->numberBetween(100, 50000),
        ];
    }
}
