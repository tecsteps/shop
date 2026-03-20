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
        $store = Store::first();

        $theme = Theme::factory()->published()->create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'version' => '1.0.0',
        ]);

        ThemeFile::factory()->create([
            'theme_id' => $theme->id,
            'path' => 'templates/index.html',
            'byte_size' => 1024,
        ]);

        ThemeFile::factory()->create([
            'theme_id' => $theme->id,
            'path' => 'templates/product.html',
            'byte_size' => 2048,
        ]);

        ThemeFile::factory()->create([
            'theme_id' => $theme->id,
            'path' => 'assets/theme.css',
            'byte_size' => 4096,
        ]);

        ThemeSettings::factory()->create([
            'theme_id' => $theme->id,
            'settings_json' => [
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over 50 EUR',
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
                ],
                'dark_mode' => 'system',
                'sections' => [
                    'hero' => [
                        'enabled' => true,
                        'heading' => 'Summer Collection',
                        'subheading' => 'Discover our latest arrivals',
                        'cta_text' => 'Shop now',
                        'cta_link' => '/collections/summer',
                        'background_image' => null,
                    ],
                    'featured_collections' => [
                        'enabled' => true,
                        'collection_ids' => [],
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
            ],
        ]);
    }
}
