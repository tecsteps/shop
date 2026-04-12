<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    public const CACHE_TTL = 300;

    /**
     * Returns the settings array for a store's active (published) theme.
     * Falls back to default settings when no published theme exists.
     *
     * @return array<string, mixed>
     */
    public function forStore(Store $store): array
    {
        return Cache::remember(
            "theme:settings:store:{$store->id}",
            self::CACHE_TTL,
            function () use ($store): array {
                $theme = Theme::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('status', ThemeStatus::Published->value)
                    ->with('settings')
                    ->latest('published_at')
                    ->first();

                if ($theme === null || $theme->settings === null) {
                    return $this->defaultSettings();
                }

                return array_merge($this->defaultSettings(), (array) $theme->settings->settings_json);
            },
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultSettings(): array
    {
        return [
            'colors' => ['primary' => '#111'],
            'announcement' => null,
            'footer_text' => '(c) Shop',
        ];
    }

    public function forgetStore(Store $store): void
    {
        Cache::forget("theme:settings:store:{$store->id}");
    }
}
