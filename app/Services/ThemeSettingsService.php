<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $settings = null;

    protected ?int $storeId = null;

    /**
     * Get the active theme settings for the current store.
     *
     * @return array<string, mixed>
     */
    public function get(?Store $store = null): array
    {
        $store = $store ?? $this->resolveStore();

        if (! $store) {
            return [];
        }

        if ($this->storeId === $store->id && $this->settings !== null) {
            return $this->settings;
        }

        $this->storeId = $store->id;
        $cacheKey = "theme_settings:{$store->id}";

        $this->settings = Cache::remember($cacheKey, 300, function () use ($store): array {
            $theme = Theme::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', ThemeStatus::Published)
                ->first();

            if (! $theme) {
                return [];
            }

            $themeSettings = ThemeSettings::query()->find($theme->id);

            return $themeSettings?->settings_json ?? [];
        });

        return $this->settings;
    }

    /**
     * Get a specific setting value using dot notation.
     */
    public function setting(string $key, mixed $default = null, ?Store $store = null): mixed
    {
        return data_get($this->get($store), $key, $default);
    }

    /**
     * Clear the theme settings cache for a store.
     */
    public function clearCache(int $storeId): void
    {
        Cache::forget("theme_settings:{$storeId}");
        $this->settings = null;
        $this->storeId = null;
    }

    protected function resolveStore(): ?Store
    {
        if (app()->bound('current_store')) {
            return app('current_store');
        }

        return null;
    }
}
