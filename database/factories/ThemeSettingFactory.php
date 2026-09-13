<?php

namespace Database\Factories;

use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThemeSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'settings_json' => ['color_scheme' => 'light'],
        ];
    }
}
