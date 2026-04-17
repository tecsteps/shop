<?php

namespace App\Services;

use App\Models\Theme;

class ThemeSettingsService
{
    /** @var array<string, mixed>|null */
    private ?array $settings = null;

    /**
     * Get a theme setting value by dot-notation key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return data_get($settings, $key, $default);
    }

    /**
     * Return all theme settings for the active theme of the current store.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $this->settings = [];

        if (! app()->bound('current_store')) {
            return $this->settings;
        }

        $store = app('current_store');

        $theme = Theme::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->with('settings')
            ->first();

        if ($theme?->settings) {
            $this->settings = $theme->settings->settings_json ?? [];
        }

        return $this->settings;
    }
}
