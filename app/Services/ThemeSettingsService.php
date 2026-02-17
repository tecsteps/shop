<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $settings = null;

    /**
     * Get a theme setting by key with optional default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Get all theme settings for the current store.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        if (! app()->bound('current_store')) {
            return $this->settings = [];
        }

        $store = app('current_store');
        $cacheKey = "theme_settings.{$store->id}";

        $this->settings = Cache::remember($cacheKey, 300, function () use ($store) {
            $theme = Theme::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', ThemeStatus::Published)
                ->latest()
                ->first();

            if (! $theme) {
                return [];
            }

            $themeSettings = ThemeSettings::query()
                ->where('theme_id', $theme->id)
                ->first();

            return $themeSettings?->settings_json ?? [];
        });

        return $this->settings;
    }

    /**
     * Clear the in-memory cache (useful for testing).
     */
    public function flush(): void
    {
        $this->settings = null;
    }
}
