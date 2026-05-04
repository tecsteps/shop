<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Database\Seeder;

class ThemeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Theme::withoutGlobalScopes()
            ->with('store')
            ->get()
            ->each(function (Theme $theme): void {
                ThemeSettings::withoutGlobalScopes()->updateOrCreate(
                    ['theme_id' => $theme->getKey()],
                    [
                        'settings_json' => app(ThemeSettingsService::class)->defaultsForStore($theme->store),
                        'updated_at' => now(),
                    ],
                );
            });
    }
}
