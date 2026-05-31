<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Services\ThemeSettingsService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThemeSettings>
 */
class ThemeSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'settings_json' => ThemeSettingsService::defaults(),
        ];
    }

    /**
     * Replace the stored settings with the given overrides merged onto defaults.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function withSettings(array $overrides): static
    {
        return $this->state(fn (): array => [
            'settings_json' => array_replace_recursive(ThemeSettingsService::defaults(), $overrides),
        ]);
    }
}
