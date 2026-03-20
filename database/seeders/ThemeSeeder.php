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
        $stores = Store::all();

        foreach ($stores as $store) {
            $theme = Theme::create([
                'store_id' => $store->id,
                'name' => 'Default Theme',
                'version' => '1.0.0',
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ]);

            $settings = $this->getSettingsForStore($store);

            ThemeSettings::create([
                'theme_id' => $theme->id,
                'settings_json' => $settings,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettingsForStore(Store $store): array
    {
        if (str_contains(strtolower($store->name), 'electronics')) {
            return [
                'primary_color' => '#0f172a',
                'secondary_color' => '#3b82f6',
                'font_family' => 'Inter, sans-serif',
                'hero_heading' => 'Acme Electronics',
                'hero_subheading' => 'Premium tech for professionals',
                'hero_cta_text' => 'Shop Featured',
                'hero_cta_link' => '/collections/featured',
                'featured_collection_handles' => ['featured'],
                'footer_text' => date('Y').' Acme Electronics. All rights reserved.',
            ];
        }

        return [
            'primary_color' => '#1a1a2e',
            'secondary_color' => '#e94560',
            'font_family' => 'Inter, sans-serif',
            'hero_heading' => 'Welcome to Acme Fashion',
            'hero_subheading' => 'Discover our curated collection of modern essentials',
            'hero_cta_text' => 'Shop New Arrivals',
            'hero_cta_link' => '/collections/new-arrivals',
            'featured_collection_handles' => ['new-arrivals', 't-shirts', 'sale'],
            'footer_text' => date('Y').' Acme Fashion. All rights reserved.',
            'show_announcement_bar' => true,
            'announcement_text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
            'products_per_page' => 12,
            'show_vendor' => true,
            'show_quantity_selector' => true,
        ];
    }
}
