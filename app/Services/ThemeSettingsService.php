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
     * Default settings used as fallback when no theme is configured.
     *
     * @var array<string, mixed>
     */
    protected array $defaults = [
        'announcement_bar' => [
            'enabled' => false,
            'text' => '',
            'link' => null,
            'background_color' => '#1a1a1a',
        ],
        'sticky_header' => false,
        'logo_url' => null,
        'colors' => [
            'primary' => '#3b82f6',
            'secondary' => '#6b7280',
            'accent' => '#f59e0b',
        ],
        'social_links' => [
            'facebook' => null,
            'instagram' => null,
            'twitter' => null,
            'tiktok' => null,
            'youtube' => null,
        ],
        'dark_mode' => 'system',
        'home_sections' => [
            ['type' => 'hero', 'enabled' => true],
            ['type' => 'featured_collections', 'enabled' => true],
            ['type' => 'featured_products', 'enabled' => true],
            ['type' => 'newsletter', 'enabled' => true],
            ['type' => 'rich_text', 'enabled' => false],
        ],
        'hero' => [
            'heading' => 'Welcome to our store',
            'subheading' => 'Discover amazing products at great prices.',
            'cta_text' => 'Shop now',
            'cta_link' => '/collections',
            'background_image' => null,
        ],
        'footer' => [
            'store_address' => null,
            'contact_email' => null,
        ],
    ];

    /**
     * Loaded settings for the current store.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $settings = null;

    /**
     * Get all theme settings for the current store, merged with defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        if (! app()->bound('current_store')) {
            return $this->defaults;
        }

        $store = app('current_store');
        $cacheKey = 'theme_settings:'.$store->id;

        $this->settings = Cache::remember($cacheKey, 300, function () use ($store): array {
            $theme = Theme::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', ThemeStatus::Published)
                ->first();

            if (! $theme) {
                return $this->defaults;
            }

            $themeSettings = ThemeSettings::find($theme->id);

            if (! $themeSettings) {
                return $this->defaults;
            }

            return array_replace_recursive($this->defaults, $themeSettings->settings_json ?? []);
        });

        return $this->settings;
    }

    /**
     * Get a specific setting value using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * Clear the cached settings (useful after updates).
     */
    public function clearCache(): void
    {
        $this->settings = null;

        if (app()->bound('current_store')) {
            $store = app('current_store');
            Cache::forget('theme_settings:'.$store->id);
        }
    }
}
