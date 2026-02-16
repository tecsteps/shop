<?php

namespace Database\Seeders;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('slug', 'acme-fashion')->firstOrFail();

        $theme = Theme::firstOrCreate(
            ['store_id' => $fashion->id, 'name' => 'Default Theme'],
            [
                'is_active' => true,
                'status' => ThemeStatus::Published,
            ]
        );

        ThemeSettings::firstOrCreate(
            ['theme_id' => $theme->id],
            [
                'settings_json' => [
                    'primary_color' => '#1a1a2e',
                    'secondary_color' => '#e94560',
                    'font_family' => 'Inter, sans-serif',
                    'hero_heading' => 'Welcome to Acme Fashion',
                    'hero_subheading' => 'Discover our curated collection of modern essentials',
                    'hero_cta_text' => 'Shop New Arrivals',
                    'hero_cta_link' => '/collections/new-arrivals',
                    'featured_collection_handles' => ['new-arrivals', 't-shirts', 'sale'],
                    'footer_text' => '2025 Acme Fashion. All rights reserved.',
                    'show_announcement_bar' => true,
                    'announcement_text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
                    'products_per_page' => 12,
                    'show_vendor' => true,
                    'show_quantity_selector' => true,
                    'announcement_bar' => [
                        'enabled' => true,
                        'text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
                    ],
                    'sticky_header' => true,
                    'hero' => [
                        'heading' => 'Welcome to Acme Fashion',
                        'subheading' => 'Discover our curated collection of modern essentials',
                        'cta_text' => 'Shop New Arrivals',
                        'cta_url' => '/collections/new-arrivals',
                    ],
                ],
            ]
        );

        $electronics = Store::where('slug', 'acme-electronics')->firstOrFail();

        $eTheme = Theme::firstOrCreate(
            ['store_id' => $electronics->id, 'name' => 'Default Theme'],
            [
                'is_active' => true,
                'status' => ThemeStatus::Published,
            ]
        );

        ThemeSettings::firstOrCreate(
            ['theme_id' => $eTheme->id],
            [
                'settings_json' => [
                    'primary_color' => '#0f172a',
                    'secondary_color' => '#3b82f6',
                    'font_family' => 'Inter, sans-serif',
                    'hero_heading' => 'Acme Electronics',
                    'hero_subheading' => 'Premium tech for professionals',
                    'hero_cta_text' => 'Shop Featured',
                    'hero_cta_link' => '/collections/featured',
                    'featured_collection_handles' => ['featured'],
                    'footer_text' => '2025 Acme Electronics. All rights reserved.',
                ],
            ]
        );
    }
}
