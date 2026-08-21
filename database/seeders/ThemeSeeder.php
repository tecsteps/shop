<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Store::query()->get() as $store) {
            $theme = Theme::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey(), 'name' => 'Baseline'],
                ['version' => '1.0.0', 'status' => 'published', 'published_at' => now()],
            );

            $theme->settings()->updateOrCreate(
                ['theme_id' => $theme->getKey()],
                ['settings_json' => ['brand' => ['primary' => '#18181b'], 'layout' => ['container' => 'wide']]],
            );
        }
    }
}
