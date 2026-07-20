<?php

namespace Database\Seeders;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeSeeder extends Seeder
{
    /**
     * Create one published theme per store with full settings (spec 07 §3.14).
     * The settings JSON follows the ThemeSettingsService::DEFAULTS structure.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $themesByHandle = [
                'acme-fashion' => [
                    'announcement' => [
                        'enabled' => true,
                        'text' => 'Free shipping on orders over 50 EUR - Use code FREESHIP',
                        'link' => null,
                    ],
                    'colors' => [
                        'primary' => '#1a1a2e',
                        'secondary' => '#e94560',
                    ],
                    'hero' => [
                        'enabled' => true,
                        'heading' => 'Welcome to Acme Fashion',
                        'subheading' => 'Discover our curated collection of modern essentials',
                        'cta_label' => 'Shop New Arrivals',
                        'cta_url' => '/collections/new-arrivals',
                    ],
                    'featured_collections' => [
                        'enabled' => true,
                        'count' => 3,
                        'collection_handles' => ['new-arrivals', 't-shirts', 'sale'],
                    ],
                    'footer' => [
                        'about' => '2025 Acme Fashion. All rights reserved.',
                    ],
                ],
                'acme-electronics' => [
                    'colors' => [
                        'primary' => '#0f172a',
                        'secondary' => '#3b82f6',
                    ],
                    'hero' => [
                        'enabled' => true,
                        'heading' => 'Acme Electronics',
                        'subheading' => 'Premium tech for professionals',
                        'cta_label' => 'Shop Featured',
                        'cta_url' => '/collections/featured',
                    ],
                    'featured_collections' => [
                        'enabled' => true,
                        'count' => 1,
                        'collection_handles' => ['featured'],
                    ],
                    'footer' => [
                        'about' => '2025 Acme Electronics. All rights reserved.',
                    ],
                ],
            ];

            foreach ($themesByHandle as $handle => $settings) {
                $store = Store::query()->where('handle', $handle)->firstOrFail();

                $theme = Theme::query()->updateOrCreate(
                    ['store_id' => $store->id, 'name' => 'Default Theme'],
                    [
                        'version' => '1.0.0',
                        'status' => ThemeStatus::Published,
                        'published_at' => now(),
                    ],
                );

                $theme->settings()->updateOrCreate(
                    ['theme_id' => $theme->id],
                    ['settings_json' => $settings],
                );
            }
        });
    }
}
