<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Loads and caches the active (published) theme settings for the current
 * store. Registered as a singleton in the AppServiceProvider. The cache is
 * invalidated whenever a ThemeSettings row is saved or deleted (see the
 * ThemeSettings model events) and expires automatically after five minutes.
 */
class ThemeSettingsService
{
    private const int CACHE_TTL_MINUTES = 5;

    /**
     * The canonical theme settings shape. Stored settings are merged on top
     * of these defaults, so the storefront always renders with a complete
     * settings array even when no theme exists yet.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'primary_color' => '#1d4ed8',
            'secondary_color' => '#3b82f6',
            'font_family' => 'Instrument Sans, sans-serif',
            'logo_url' => null,
            'sticky_header' => true,
            'dark_mode' => 'system',
            'show_announcement_bar' => false,
            'announcement_text' => '',
            'announcement_link' => null,
            'hero_heading' => 'Welcome to our store',
            'hero_subheading' => 'Discover our latest products and collections.',
            'hero_cta_text' => 'Shop now',
            'hero_cta_link' => '/collections',
            'hero_image_url' => null,
            'featured_collection_handles' => [],
            'featured_products_count' => 8,
            'featured_products_collection_handle' => null,
            'show_newsletter' => true,
            'rich_text_html' => null,
            'footer_text' => null,
            'social_links' => [],
            'products_per_page' => 12,
            'show_vendor' => true,
            'show_quantity_selector' => true,
            'sections' => [
                'hero',
                'featured-collections',
                'featured-products',
                'newsletter',
                'rich-text',
            ],
        ];
    }

    /**
     * All effective settings for the given store (defaults merged with the
     * active theme's stored settings). Falls back to plain defaults when no
     * store is resolvable or the store has no published theme.
     *
     * @return array<string, mixed>
     */
    public function all(?Store $store = null): array
    {
        $store ??= $this->currentStore();

        if ($store === null) {
            return static::defaults();
        }

        $stored = Cache::remember(
            $this->cacheKey($store->getKey()),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->loadStoredSettings($store),
        );

        return array_replace(static::defaults(), $stored);
    }

    /**
     * Read a single setting using dot notation, e.g. get('hero_heading').
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    /**
     * Forget the cached settings for a store.
     */
    public function forget(int $storeId): void
    {
        Cache::forget($this->cacheKey($storeId));
    }

    /**
     * Load the stored settings of the store's active (published) theme.
     *
     * @return array<string, mixed>
     */
    protected function loadStoredSettings(Store $store): array
    {
        $theme = Theme::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('status', ThemeStatus::Published)
            ->orderByDesc('published_at')
            ->with('settings')
            ->first();

        return $theme?->settings?->settings_json ?? [];
    }

    protected function currentStore(): ?Store
    {
        return app()->bound('current_store') ? app('current_store') : null;
    }

    protected function cacheKey(int $storeId): string
    {
        return "theme_settings:{$storeId}";
    }
}
