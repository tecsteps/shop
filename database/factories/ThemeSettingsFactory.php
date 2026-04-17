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
     * Define the model's default state.
     *
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
                'logo_url' => null,
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#6b7280',
                    'accent' => '#f59e0b',
                ],
                'social_links' => [
                    'facebook' => null,
                    'instagram' => null,
                    'twitter' => null,
                    'tiktok' => null,
                    'youtube' => null,
                ],
                'dark_mode' => 'system',
                'home_sections' => [
                    ['type' => 'hero', 'enabled' => true],
                    ['type' => 'featured_collections', 'enabled' => true],
                    ['type' => 'featured_products', 'enabled' => true],
                    ['type' => 'newsletter', 'enabled' => true],
                    ['type' => 'rich_text', 'enabled' => false],
                ],
                'hero' => [
                    'heading' => 'Welcome to our store',
                    'subheading' => 'Discover amazing products at great prices.',
                    'cta_text' => 'Shop now',
                    'cta_link' => '/collections',
                    'background_image' => null,
                ],
                'footer' => [
                    'store_address' => null,
                    'contact_email' => null,
                ],
            ],
        ];
    }
}
