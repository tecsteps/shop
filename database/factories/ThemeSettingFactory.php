<?php

namespace Database\Factories;

use App\Models\ThemeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThemeSetting>
 */
class ThemeSettingFactory extends Factory
{
    protected $model = ThemeSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => ThemeFactory::new(),
            'settings_json' => ['primary_color' => fake()->safeHexColor()],
        ];
    }
}
