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

    public function definition(): array
    {
        $path = 'templates/'.$this->faker->unique()->slug(2).'.blade.php';

        return [
            'theme_id' => Theme::factory(),
            'path' => $path,
            'storage_key' => 'themes/'.$this->faker->uuid().'/'.$path,
            'sha256' => hash('sha256', $this->faker->sentence()),
            'byte_size' => $this->faker->numberBetween(100, 50000),
        ];
    }
}
