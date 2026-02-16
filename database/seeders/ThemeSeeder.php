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
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

        $theme = Theme::create([
            'store_id' => $store->id,
            'name' => 'Default Theme',
            'is_active' => true,
            'status' => ThemeStatus::Published,
        ]);

        ThemeSettings::create([
            'theme_id' => $theme->id,
            'settings_json' => [
                'announcement_bar' => [
                    'enabled' => true,
                    'text' => 'Free shipping on orders over $50!',
                ],
                'sticky_header' => true,
                'hero' => [
                    'heading' => 'Welcome to Acme Fashion',
                    'subheading' => 'Discover our curated collection of premium products.',
                    'cta_text' => 'Shop Collections',
                    'cta_url' => '/collections',
                ],
            ],
        ]);
    }
}
