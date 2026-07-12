<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['acme-fashion', 'acme-electronics'] as $handle) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();
            $fashion = $handle === 'acme-fashion';
            $theme = Theme::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'name' => 'Default Theme'], [
                'version' => '1.0.0', 'status' => 'published', 'published_at' => now(),
            ]);
            $settings = [
                'primary_color' => $fashion ? '#1a1a2e' : '#0f172a',
                'secondary_color' => $fashion ? '#e94560' : '#3b82f6',
                'font_family' => 'Inter, sans-serif',
                'hero_heading' => $fashion ? 'Welcome to Acme Fashion' : 'Acme Electronics',
                'hero_subheading' => $fashion ? 'Discover our curated collection of modern essentials' : 'Premium tech for professionals',
                'hero_cta_text' => $fashion ? 'Shop New Arrivals' : 'Shop Featured',
                'hero_cta_link' => $fashion ? '/collections/new-arrivals' : '/collections/featured',
                'featured_collection_handles' => $fashion ? ['new-arrivals', 't-shirts', 'sale'] : ['featured'],
                'footer_text' => $fashion ? '2025 Acme Fashion. All rights reserved.' : '2025 Acme Electronics. All rights reserved.',
                'show_announcement_bar' => $fashion,
                'announcement_text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
                'products_per_page' => 12,
                'show_vendor' => true,
                'show_quantity_selector' => true,
                'colors' => ['primary' => $fashion ? '#1a1a2e' : '#0f172a', 'secondary' => $fashion ? '#e94560' : '#3b82f6', 'accent' => '#f59e0b'],
                'announcement' => ['enabled' => $fashion, 'text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP', 'background' => '#18181b'],
                'home' => ['hero' => ['enabled' => true, 'heading' => $fashion ? 'Welcome to Acme Fashion' : 'Acme Electronics', 'subheading' => $fashion ? 'Discover our curated collection of modern essentials' : 'Premium tech for professionals', 'cta_label' => $fashion ? 'Shop New Arrivals' : 'Shop Featured', 'cta_url' => $fashion ? '/collections/new-arrivals' : '/collections/featured']],
            ];
            $theme->settings()->updateOrCreate(['theme_id' => $theme->id], ['settings_json' => $settings]);
        }
    }
}
