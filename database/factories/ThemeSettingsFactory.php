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
                    'text' => 'Free shipping on orders over 75 EUR',
                    'link' => '/collections/summer-essentials',
                ],
                'home' => [
                    'sections' => [
                        ['key' => 'hero', 'enabled' => true],
                        ['key' => 'featured_collections', 'enabled' => true],
                        ['key' => 'featured_products', 'enabled' => true],
                        ['key' => 'newsletter', 'enabled' => true],
                        ['key' => 'rich_text', 'enabled' => true],
                    ],
                    'hero_heading' => 'Acme Fashion',
                    'hero_subheading' => 'Everyday essentials with sharp fits and breathable fabrics.',
                    'hero_cta_label' => 'Shop new arrivals',
                    'hero_cta_url' => '/collections/summer-essentials',
                    'featured_collections_heading' => 'Featured Collections',
                    'featured_collections_subheading' => 'Curated selections from Acme Fashion.',
                    'featured_collections_count' => 3,
                    'featured_products_heading' => 'Featured Products',
                    'featured_products_count' => 6,
                    'newsletter_heading' => 'Stay in the loop',
                    'newsletter_subheading' => 'Subscribe for exclusive offers and updates.',
                    'rich_text_heading' => 'Designed for daily wear',
                    'rich_text_html' => '<p>Clean silhouettes, durable fabrics, and useful details shape every release.</p>',
                ],
                'footer' => [
                    'contact_email' => 'support@acme.test',
                ],
            ],
        ];
    }
}
