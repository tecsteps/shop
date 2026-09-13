<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThemeFileFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'path' => 'templates/'.fake()->unique()->slug().'.liquid',
            'storage_key' => 'themes/'.fake()->uuid().'.liquid',
            'sha256' => hash('sha256', fake()->uuid()),
            'byte_size' => fake()->numberBetween(100, 100000),
        ];
    }
}
