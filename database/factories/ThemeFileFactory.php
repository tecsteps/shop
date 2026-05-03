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
        $path = 'sections/'.fake()->unique()->slug().'.blade.php';

        return [
            'theme_id' => Theme::factory(),
            'path' => $path,
            'storage_key' => 'themes/'.Str::random(12).'/'.$path,
            'sha256' => hash('sha256', $path.fake()->uuid()),
            'byte_size' => fake()->numberBetween(128, 4096),
        ];
    }
}
