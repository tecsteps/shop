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
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 50 EUR',
                    'link' => null,
                    'bg_color' => '#1f2937',
                ],
                'sticky_header' => true,
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#6b7280',
                    'accent' => '#f59e0b',
                ],
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text'],
                'hero' => [
                    'heading' => 'Welcome to Our Store',
                    'subheading' => 'Discover our latest collection',
                    'cta_text' => 'Shop Now',
                    'cta_link' => '/collections',
                    'image' => null,
                ],
                'footer' => [
                    'social_links' => [],
                ],
                'dark_mode' => 'system',
            ],
        ];
    }
}
