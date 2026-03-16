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
     * Get a theme setting value for the current store.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return data_get($settings, $key, $default);
    }

    /**
     * Get all theme settings for the current store.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $store = $this->resolveStore();

        if (! $store) {
            return [];
        }

        if ($this->settings !== null && $this->storeId === $store->id) {
            return $this->settings;
        }

        $this->storeId = $store->id;

        $this->settings = Cache::remember(
            "theme_settings:{$store->id}",
            300,
            function () use ($store): array {
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
            }
        );

        return $this->settings;
    }

    /**
     * Clear the cached settings.
     */
    public function clearCache(?Store $store = null): void
    {
        $store = $store ?? $this->resolveStore();

        if ($store) {
            Cache::forget("theme_settings:{$store->id}");
        }

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
