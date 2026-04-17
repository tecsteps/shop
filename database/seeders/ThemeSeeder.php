<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        // Acme Fashion theme
        $fashionTheme = Theme::factory()->published()->create([
            'store_id' => $fashion->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
        ]);

        ThemeFile::factory()->create(['theme_id' => $fashionTheme->id, 'path' => 'templates/index.html', 'byte_size' => 1024]);
        ThemeFile::factory()->create(['theme_id' => $fashionTheme->id, 'path' => 'templates/product.html', 'byte_size' => 2048]);
        ThemeFile::factory()->create(['theme_id' => $fashionTheme->id, 'path' => 'assets/theme.css', 'byte_size' => 4096]);

        ThemeSettings::factory()->create([
            'theme_id' => $fashionTheme->id,
            'settings_json' => [
                'primary_color' => '#1a1a2e',
                'secondary_color' => '#e94560',
                'font_family' => 'Inter, sans-serif',
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
                    'link' => '/collections/sale',
                ],
                'header' => [
                    'sticky' => true,
                    'logo_url' => null,
                ],
                'footer' => [
                    'social_links' => [
                        ['platform' => 'facebook', 'url' => 'https://facebook.com/acme'],
                        ['platform' => 'instagram', 'url' => 'https://instagram.com/acme'],
                    ],
                    'footer_text' => '2025 Acme Fashion. All rights reserved.',
                ],
                'dark_mode' => 'system',
                'sections' => [
                    'hero' => [
                        'enabled' => true,
                        'heading' => 'Welcome to Acme Fashion',
                        'subheading' => 'Discover our curated collection of modern essentials',
                        'cta_text' => 'Shop New Arrivals',
                        'cta_link' => '/collections/new-arrivals',
                        'background_image' => null,
                    ],
                    'featured_collections' => [
                        'enabled' => true,
                        'collection_handles' => ['new-arrivals', 't-shirts', 'sale'],
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
                'products_per_page' => 12,
                'show_vendor' => true,
                'show_quantity_selector' => true,
            ],
        ]);

        // Acme Electronics theme
        $electronicsTheme = Theme::factory()->published()->create([
            'store_id' => $electronics->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
        ]);

        ThemeSettings::factory()->create([
            'theme_id' => $electronicsTheme->id,
            'settings_json' => [
                'primary_color' => '#0f172a',
                'secondary_color' => '#3b82f6',
                'font_family' => 'Inter, sans-serif',
                'announcement_bar' => ['enabled' => false],
                'header' => ['sticky' => true, 'logo_url' => null],
                'footer' => [
                    'social_links' => [],
                    'footer_text' => '2025 Acme Electronics. All rights reserved.',
                ],
                'dark_mode' => 'system',
                'sections' => [
                    'hero' => [
                        'enabled' => true,
                        'heading' => 'Acme Electronics',
                        'subheading' => 'Premium tech for professionals',
                        'cta_text' => 'Shop Featured',
                        'cta_link' => '/collections/featured',
                    ],
                    'featured_collections' => [
                        'enabled' => true,
                        'collection_handles' => ['featured'],
                    ],
                ],
                'section_order' => ['hero', 'featured_collections'],
            ],
        ]);
    }
}
