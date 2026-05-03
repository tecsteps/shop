<?php

namespace Database\Seeders;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        $theme = Theme::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'name' => 'Default Storefront',
            ],
            [
                'version' => '1.0.0',
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ],
        );

        ThemeSettings::query()->updateOrCreate(
            ['theme_id' => $theme->id],
            [
                'settings_json' => [
                    'announcement' => [
                        'enabled' => true,
                        'text' => 'Free shipping on orders over 75 EUR',
                        'link' => '/collections/summer-essentials',
                    ],
                    'home' => [
                        'hero_heading' => 'Acme Fashion',
                        'hero_subheading' => 'Light layers, clean lines, and durable everyday staples.',
                        'hero_cta_label' => 'Shop summer essentials',
                        'hero_cta_url' => '/collections/summer-essentials',
                    ],
                    'footer' => [
                        'contact_email' => 'support@acme.test',
                    ],
                ],
            ],
        );
    }
}
