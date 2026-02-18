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
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over $50!',
                    'link' => '/collections',
                    'background_color' => '#1a1a1a',
                ],
                'sticky_header' => true,
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#64748b',
                    'accent' => '#f59e0b',
                ],
                'home_sections' => ['hero', 'featured_collections', 'featured_products', 'newsletter'],
                'hero' => [
                    'heading' => 'Welcome to Acme Fashion',
                    'subheading' => 'Discover the latest trends',
                    'cta_text' => 'Shop Now',
                    'cta_link' => '/collections',
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
}
