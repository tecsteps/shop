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
        $path = fake()->randomElement([
            'templates/index.liquid',
            'templates/product.liquid',
            'sections/header.liquid',
            'snippets/cart.liquid',
            'assets/styles.css',
        ]);

        return [
            'theme_id' => Theme::factory(),
            'path' => $path.'.'.fake()->unique()->randomNumber(5),
            'storage_key' => 'themes/'.fake()->uuid().'/'.$path,
            'sha256' => hash('sha256', fake()->text(50)),
            'byte_size' => fake()->numberBetween(100, 50_000),
        ];
    }
}
