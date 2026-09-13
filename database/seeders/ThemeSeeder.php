<?php

namespace Database\Seeders;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSetting;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $this->seedTheme($fashion, [
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
        ]);

        $this->seedTheme($electronics, [
            'primary_color' => '#0f172a',
            'secondary_color' => '#3b82f6',
            'font_family' => 'Inter, sans-serif',
            'hero_heading' => 'Acme Electronics',
            'hero_subheading' => 'Premium tech for professionals',
            'hero_cta_text' => 'Shop Featured',
            'hero_cta_link' => '/collections/featured',
            'featured_collection_handles' => ['featured'],
            'footer_text' => '2025 Acme Electronics. All rights reserved.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function seedTheme(Store $store, array $settings): void
    {
        $theme = Theme::query()->updateOrCreate(
            ['store_id' => $store->getKey(), 'name' => 'Default Theme'],
            [
                'version' => '1.0.0',
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ]
        );

        ThemeSetting::query()->updateOrCreate(
            ['theme_id' => $theme->getKey()],
            ['settings_json' => $settings]
        );
    }
}
