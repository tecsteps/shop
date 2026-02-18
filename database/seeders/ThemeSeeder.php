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
        $this->seedAcmeFashion();
        $this->seedAcmeElectronics();
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->first();
        if (! $store) {
            return;
        }

        $theme = Theme::factory()->create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ]);

        ThemeSettings::factory()->create([
            'theme_id' => $theme->id,
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
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
                    'link' => '/collections',
                    'background_color' => '#1a1a1a',
                ],
                'sticky_header' => true,
                'products_per_page' => 12,
                'show_vendor' => true,
                'show_quantity_selector' => true,
                'colors' => [
                    'primary' => '#1a1a2e',
                    'secondary' => '#e94560',
                    'accent' => '#f59e0b',
                ],
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter'],
                'hero' => [
                    'heading' => 'Welcome to Acme Fashion',
                    'subheading' => 'Discover our curated collection of modern essentials',
                    'cta_text' => 'Shop New Arrivals',
                    'cta_link' => '/collections/new-arrivals',
                ],
                'footer' => [
                    'social_links' => [
                        'twitter' => 'https://twitter.com/acme',
                        'instagram' => 'https://instagram.com/acme',
                    ],
                ],
            ],
        ]);
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::query()->where('handle', 'acme-electronics')->first();
        if (! $store) {
            return;
        }

        $theme = Theme::factory()->create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ]);

        ThemeSettings::factory()->create([
            'theme_id' => $theme->id,
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
        ]);
    }
}
