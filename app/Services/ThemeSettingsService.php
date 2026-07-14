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
        $previewId = request()->query('theme_preview');
        if (is_numeric($previewId) && auth()->guard('web')->check()
            && auth()->guard('web')->user()->stores()->whereKey($store->id)->exists()) {
            $theme = Theme::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->with('settings')
                ->find((int) $previewId);
            if ($theme !== null) {
                $sessionSettings = request()->session()->get("theme_preview.{$theme->id}");

                return is_array($sessionSettings)
                    && (int) ($sessionSettings['user_id'] ?? 0) === (int) auth()->guard('web')->id()
                    && is_array($sessionSettings['settings'] ?? null)
                    ? $sessionSettings['settings']
                    : (array) ($theme->settings?->settings_json ?? []);
            }
        }

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
