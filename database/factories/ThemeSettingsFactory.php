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
                ],
                'header' => [
                    'sticky' => true,
                    'logo_url' => null,
                ],
                'footer' => [
                    'social_links' => [],
                ],
                'dark_mode' => 'system',
                'sections' => [
                    'hero' => [
                        'enabled' => true,
                        'heading' => 'Welcome to our store',
                        'subheading' => 'Discover amazing products',
                        'cta_text' => 'Shop now',
                        'cta_link' => '/collections',
                        'background_image' => null,
                    ],
                    'featured_collections' => [
                        'enabled' => true,
                        'collection_ids' => [],
                    ],
                    'featured_products' => [
                        'enabled' => true,
                        'product_ids' => [],
                    ],
                    'newsletter' => [
                        'enabled' => true,
                    ],
                    'rich_text' => [
                        'enabled' => false,
                        'content' => '',
                    ],
                ],
                'section_order' => ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text'],
            ],
        ];
    }
}
