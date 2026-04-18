<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    protected const CACHE_TTL_SECONDS = 300;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $cache = [];

    /**
     * @return array<string, mixed>
     */
    public function forStore(?Store $store = null): array
    {
        $store = $store ?? $this->currentStore();

        if (! $store) {
            return [];
        }

        if (array_key_exists($store->id, $this->cache)) {
            return $this->cache[$store->id];
        }

        $settings = Cache::remember(
            "theme_settings:{$store->id}",
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->loadSettings($store)
        );

        return $this->cache[$store->id] = $settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->forStore(), $key, $default);
    }

    public function forget(Store $store): void
    {
        Cache::forget("theme_settings:{$store->id}");
        unset($this->cache[$store->id]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadSettings(Store $store): array
    {
        $theme = Theme::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', ThemeStatus::Published->value)
            ->orderByDesc('published_at')
            ->first();

        if (! $theme) {
            return [];
        }

        $settings = $theme->settings;

        return $settings?->settings_json ?? [];
    }

    protected function currentStore(): ?Store
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        return $store instanceof Store ? $store : null;
    }
}
