<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ThemeSettings> */
class ThemeSettingsFactory extends Factory
{
    protected $model = ThemeSettings::class;

    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'settings_json' => [
                'announcement_bar' => ['enabled' => false],
                'sticky_header' => true,
            ],
        ];
    }
}
