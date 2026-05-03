<?php

namespace Database\Factories;

use App\Models\Theme;
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
            'settings_json' => [
                'announcement' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 75.00 EUR',
                    'url' => null,
                ],
                'header' => [
                    'sticky' => true,
                    'main_menu' => 'main-menu',
                ],
                'footer' => [
                    'menu' => 'footer-menu',
                    'tagline' => 'A self-contained demo storefront.',
                ],
            ],
        ];
    }
}
