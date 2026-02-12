<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ThemeSettings> */
class ThemeSettingsFactory extends Factory
{
    protected $model = ThemeSettings::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'theme_id' => Theme::factory(),
            'settings_json' => [
                'primary_color' => '#1a1a2e',
                'secondary_color' => '#e94560',
                'font_family' => 'Inter, sans-serif',
                'hero_heading' => 'Welcome to Our Store',
                'hero_subheading' => 'Discover our curated collection',
                'hero_cta_text' => 'Shop Now',
                'hero_cta_link' => '/collections',
                'featured_collection_handles' => [],
                'footer_text' => 'All rights reserved.',
                'show_announcement_bar' => true,
                'announcement_text' => 'Free shipping on orders over 50 EUR',
                'products_per_page' => 12,
                'show_vendor' => true,
                'show_quantity_selector' => true,
            ],
        ];
    }
}
