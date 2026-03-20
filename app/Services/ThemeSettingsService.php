<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    private ?ThemeSettings $settings = null;

    public function load(): ?ThemeSettings
    {
        if ($this->settings) {
            return $this->settings;
        }

        if (! app()->bound('current_store')) {
            return null;
        }

        $store = app('current_store');

        $this->settings = Cache::remember(
            "theme_settings:{$store->id}",
            300,
            function () use ($store) {
                $theme = Theme::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('status', ThemeStatus::Published)
                    ->first();

                if (! $theme) {
                    return null;
                }

                return $theme->settings;
            }
        );

        return $this->settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->load();

        if (! $settings) {
            return $default;
        }

        return $settings->get($key, $default);
    }

    public function all(): array
    {
        $settings = $this->load();

        return $settings ? ($settings->settings_json ?? []) : [];
    }

    public function reset(): void
    {
        $this->settings = null;
    }
}
