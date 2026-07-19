<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    /**
     * Cache lifetime for the resolved settings (5 minutes).
     */
    public const TTL_SECONDS = 300;

    /**
     * Default settings used when the store has no published theme, and the
     * base layer every published theme's settings are merged onto.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'announcement' => [
            'enabled' => false,
            'text' => '',
            'link' => null,
        ],
        'header' => [
            'sticky' => false,
            'logo_url' => null,
        ],
        'colors' => [
            'primary' => '#2563eb',
            'secondary' => '#64748b',
            'accent' => '#f59e0b',
        ],
        'dark_mode' => 'system',
        'sections_order' => ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text'],
        'hero' => [
            'enabled' => true,
            'heading' => null,
            'subheading' => null,
            'cta_label' => 'Shop now',
            'cta_url' => '/collections',
            'image_url' => null,
        ],
        'featured_collections' => [
            'enabled' => true,
            'count' => 3,
            'collection_handles' => [],
        ],
        'featured_products' => [
            'enabled' => true,
            'count' => 8,
            'collection_handle' => null,
        ],
        'newsletter' => [
            'enabled' => true,
        ],
        'rich_text' => [
            'enabled' => false,
            'html' => null,
        ],
        'footer' => [
            'about' => null,
            'social' => [],
        ],
        'seo' => [
            'description' => null,
        ],
    ];

    /**
     * All resolved settings for the current store: the published theme's
     * settings merged onto the defaults, cached for 5 minutes per store.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! app()->bound('current_store')) {
            return self::DEFAULTS;
        }

        $storeId = (int) app('current_store')->getKey();

        return Cache::remember(
            "theme_settings:{$storeId}",
            self::TTL_SECONDS,
            fn (): array => $this->loadSettings($storeId),
        );
    }

    /**
     * Get a single setting by dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Forget the cached settings of a store.
     */
    public function invalidate(?int $storeId): void
    {
        if ($storeId === null) {
            return;
        }

        Cache::forget("theme_settings:{$storeId}");
    }

    /**
     * Load the published theme's settings for the store merged onto defaults.
     *
     * @return array<string, mixed>
     */
    private function loadSettings(int $storeId): array
    {
        $theme = Theme::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('status', ThemeStatus::Published)
            ->latest('published_at')
            ->first();

        $settings = $theme?->settings?->settings_json ?? [];

        return array_replace_recursive(self::DEFAULTS, $settings);
    }
}
