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
        $store = Store::first();

        $theme = Theme::factory()->published()->create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
        ]);

        ThemeSettings::factory()->create([
            'theme_id' => $theme->id,
            'settings_json' => [
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 50 EUR',
                    'link' => null,
                    'bg_color' => '#1f2937',
                ],
                'sticky_header' => true,
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#6b7280',
                    'accent' => '#f59e0b',
                ],
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text'],
                'hero' => [
                    'heading' => 'Welcome to Acme Store',
                    'subheading' => 'Discover our latest collection of premium products.',
                    'cta_text' => 'Shop Now',
                    'cta_link' => '/collections',
                    'image' => null,
                ],
                'footer' => [
                    'social_links' => [
                        'facebook' => 'https://facebook.com',
                        'instagram' => 'https://instagram.com',
                    ],
                ],
                'dark_mode' => 'system',
            ],
        ]);
    }
}
