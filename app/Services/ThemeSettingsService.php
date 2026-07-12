<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

final class ThemeSettingsService
{
    /** @return array<string, mixed> */
    public function forStore(Store $store): array
    {
        return Cache::remember("theme-settings:{$store->id}", now()->addMinutes(5), function () use ($store): array {
            $theme = Theme::withoutGlobalScopes()->where('store_id', $store->id)->where('status', 'published')->with('settings')->first();

            return (array) ($theme?->settings?->settings_json ?? [
                'primary_color' => '#0f766e',
                'accent_color' => '#f59e0b',
                'font_heading' => 'Inter',
                'announcement' => 'Free shipping on orders over 75.00 EUR',
            ]);
        });
    }
}
