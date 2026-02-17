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
                    'text' => 'Free shipping on orders over $50!',
                    'link' => null,
                    'background_color' => '#1a1a2e',
                ],
                'sticky_header' => true,
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter'],
                'hero' => [
                    'heading' => 'Welcome to Our Store',
                    'subheading' => 'Discover amazing products at great prices.',
                    'cta_text' => 'Shop Now',
                    'cta_link' => '/collections',
                    'background_image' => null,
                ],
                'featured_collections_count' => 4,
                'featured_products_count' => 8,
                'colors' => [
                    'primary' => '#2563eb',
                    'secondary' => '#64748b',
                    'accent' => '#f59e0b',
                ],
                'footer' => [
                    'social_links' => [],
                ],
                'dark_mode' => 'system',
            ],
        ];
    }
}
