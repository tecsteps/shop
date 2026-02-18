<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThemeSettings>
 */
class ThemeSettingsFactory extends Factory
{
    protected $model = ThemeSettings::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'settings_json' => [
                'announcement_bar' => [
                    'enabled' => false,
                    'text' => 'Free shipping on orders over $50',
                    'link' => null,
                    'background_color' => '#1a1a1a',
                ],
                'sticky_header' => true,
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                    'accent' => '#f59e0b',
                ],
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter'],
            ],
        ];
    }
}
