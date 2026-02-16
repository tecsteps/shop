<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ThemeFile> */
class ThemeFileFactory extends Factory
{
    protected $model = ThemeFile::class;

    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'path' => 'templates/'.fake()->word().'.blade.php',
            'content' => '<div>'.fake()->sentence().'</div>',
        ];
    }
}
