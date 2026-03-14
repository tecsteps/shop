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
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $theme = Theme::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'status' => ThemeStatus::Published,
            'is_active' => true,
        ]);

        ThemeSettings::create([
            'theme_id' => $theme->id,
            'settings_json' => [
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 50 EUR!',
                    'bg_color' => '#1a1a2e',
                    'text_color' => '#ffffff',
                ],
                'hero' => [
                    'enabled' => true,
                    'title' => 'Welcome to Acme Fashion',
                    'subtitle' => 'Discover our latest collection',
                    'cta_text' => 'Shop Now',
                    'cta_url' => '/collections/new-arrivals',
                ],
                'featured_collections' => [
                    'enabled' => true,
                    'title' => 'Shop by Category',
                    'collection_handles' => ['t-shirts', 'new-arrivals', 'sale'],
                ],
                'footer' => [
                    'copyright' => '2026 Acme Fashion. All rights reserved.',
                    'links' => [
                        ['title' => 'About', 'url' => '/pages/about'],
                        ['title' => 'Contact', 'url' => '/pages/contact'],
                    ],
                ],
            ],
        ]);
    }
}
