<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeSeeder extends Seeder
{
    use SeedsDemoData;

    /**
     * Seed published themes (with files and settings) for every store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedTheme('acme-fashion', [
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

            $this->seedTheme('acme-electronics', [
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
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function seedTheme(string $storeHandle, array $settings): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        $theme = Theme::updateOrCreate(
            ['store_id' => $store->id, 'name' => 'Default Theme', 'version' => '1.0.0'],
            [
                'status' => 'published',
                'published_at' => now(),
            ],
        );

        $files = [
            'layout/theme.liquid' => '<html><head><title>{{ page_title }}</title>{{ content_for_header }}</head><body>{{ content_for_layout }}</body></html>',
            'templates/index.liquid' => '{{ hero }} {{ featured_collections }}',
            'templates/product.liquid' => '{{ product }}',
            'templates/collection.liquid' => '{{ collection }}',
            'assets/theme.css' => ':root { --color-primary: '.$settings['primary_color'].'; --color-secondary: '.$settings['secondary_color'].'; }',
            'assets/theme.js' => 'console.log("Acme theme loaded");',
        ];

        $theme->files()->delete();

        foreach ($files as $path => $content) {
            ThemeFile::create([
                'theme_id' => $theme->id,
                'path' => $path,
                'storage_key' => 'themes/'.$theme->id.'/'.$path,
                'sha256' => hash('sha256', $content),
                'byte_size' => strlen($content),
            ]);
        }

        ThemeSettings::updateOrCreate(
            ['theme_id' => $theme->id],
            ['settings_json' => $settings, 'updated_at' => now()],
        );
    }
}
