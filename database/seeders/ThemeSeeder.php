<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        // Acme Fashion theme
        $fashionTheme = Theme::factory()->published()->create([
            'store_id' => $fashion->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
        ]);

        ThemeSettings::factory()->create([
            'theme_id' => $fashionTheme->id,
            'settings_json' => [
                'colors' => [
                    'primary' => '#1a1a2e',
                    'secondary' => '#e94560',
                ],
                'font_family' => 'Inter, sans-serif',
                'hero' => [
                    'heading' => 'Welcome to Acme Fashion',
                    'subheading' => 'Discover our curated collection of modern essentials',
                    'cta_text' => 'Shop New Arrivals',
                    'cta_link' => '/collections/new-arrivals',
                ],
                'featured_collection_handles' => ['new-arrivals', 't-shirts', 'sale'],
                'footer' => [
                    'text' => '2025 Acme Fashion. All rights reserved.',
                    'social_links' => [],
                ],
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
                    'link' => null,
                    'background_color' => '#1a1a2e',
                ],
                'sticky_header' => true,
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter'],
                'featured_collections_count' => 4,
                'featured_products_count' => 8,
                'products_per_page' => 12,
                'show_vendor' => true,
                'show_quantity_selector' => true,
                'dark_mode' => 'system',
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
                'colors' => [
                    'primary' => '#0f172a',
                    'secondary' => '#3b82f6',
                ],
                'font_family' => 'Inter, sans-serif',
                'hero' => [
                    'heading' => 'Acme Electronics',
                    'subheading' => 'Premium tech for professionals',
                    'cta_text' => 'Shop Featured',
                    'cta_link' => '/collections/featured',
                ],
                'featured_collection_handles' => ['featured'],
                'footer' => [
                    'text' => '2025 Acme Electronics. All rights reserved.',
                    'social_links' => [],
                ],
                'announcement_bar' => [
                    'enabled' => false,
                    'text' => '',
                ],
                'sticky_header' => true,
                'home_sections' => ['hero', 'featured_collections', 'featured_products'],
                'featured_collections_count' => 2,
                'featured_products_count' => 8,
                'products_per_page' => 12,
                'dark_mode' => 'system',
            ],
        ]);
    }
}
