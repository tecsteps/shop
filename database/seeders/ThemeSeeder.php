<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSetting;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get();

        foreach ($stores as $store) {
            $theme = Theme::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'name' => 'Default Theme',
                ],
                [
                    'version' => '1.0.0',
                    'status' => 'published',
                    'published_at' => now()->subDay(),
                ]
            );

            ThemeSetting::query()->updateOrCreate(
                ['theme_id' => $theme->id],
                [
                    'settings_json' => [
                        'accent_color' => $store->handle === 'acme-fashion' ? '#111827' : '#0F766E',
                        'show_announcement_bar' => true,
                        'announcement_text' => 'Free shipping on selected orders.',
                    ],
                ]
            );
        }
    }
}
