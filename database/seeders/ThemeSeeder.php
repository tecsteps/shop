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
        Store::query()->each(function (Store $store): void {
            $theme = Theme::query()->firstOrCreate(
                ['store_id' => $store->getKey(), 'name' => 'Default'],
                [
                    'version' => '1.0.0',
                    'status' => ThemeStatus::Published->value,
                    'published_at' => now(),
                ],
            );

            ThemeSettings::query()->updateOrCreate(
                ['theme_id' => $theme->getKey()],
                ['settings_json' => json_encode([
                    'announcement' => [
                        'enabled' => true,
                        'text' => 'Free shipping on orders over $50',
                        'link' => null,
                    ],
                    'colors' => [
                        'primary' => '#111827',
                        'accent' => '#6366f1',
                    ],
                ], JSON_THROW_ON_ERROR)],
            );
        });
    }
}
