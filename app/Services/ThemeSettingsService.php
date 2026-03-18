<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return $this->defaults();
        }

        $cacheKey = "theme_settings:{$store->id}";

        return Cache::remember($cacheKey, 300, function () use ($store) {
            $theme = Theme::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', ThemeStatus::Published)
                ->first();

            if (! $theme || ! $theme->settings) {
                return $this->defaults();
            }

            return array_merge($this->defaults(), $theme->settings->settings_json ?? []);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'announcement_bar_enabled' => false,
            'announcement_bar_text' => '',
            'announcement_bar_link' => '',
            'announcement_bar_bg_color' => '#1f2937',
            'sticky_header' => false,
            'hero_heading' => 'Welcome to our store',
            'hero_subheading' => 'Discover our latest collection',
            'hero_cta_text' => 'Shop now',
            'hero_cta_link' => '/collections',
            'featured_collections_count' => 4,
            'featured_products_count' => 8,
            'social_facebook' => '',
            'social_instagram' => '',
            'social_twitter' => '',
        ];
    }
}
