<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Loads and caches the active (published) theme settings for the current store.
 *
 * Registered as a singleton in {@see \App\Providers\AppServiceProvider}. The
 * storefront and admin read settings through this service rather than touching
 * the {@see \App\Models\ThemeSettings} model directly, so callers get a single
 * merged settings array (stored overrides on top of {@see self::defaults()})
 * regardless of whether a store has customised its theme yet.
 *
 * Settings are resolved per store and cached for five minutes. Persisting new
 * settings (admin) should call {@see self::forget()} to bust the cache.
 */
class ThemeSettingsService
{
    /**
     * Cache TTL (seconds) for resolved theme settings.
     */
    private const CACHE_TTL = 300;

    /**
     * In-request memo of resolved settings, keyed by store id.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $memo = [];

    /**
     * The fully merged settings for the current (or given) store's published theme.
     *
     * @return array<string, mixed>
     */
    public function all(?Store $store = null): array
    {
        $store ??= $this->currentStore();

        if ($store === null) {
            return self::defaults();
        }

        if (isset($this->memo[$store->id])) {
            return $this->memo[$store->id];
        }

        $settings = Cache::remember(
            self::cacheKey($store->id),
            self::CACHE_TTL,
            fn (): array => $this->resolve($store),
        );

        return $this->memo[$store->id] = $settings;
    }

    /**
     * Read a single setting using dot notation, falling back to a default.
     */
    public function get(string $key, mixed $default = null, ?Store $store = null): mixed
    {
        return Arr::get($this->all($store), $key, $default);
    }

    /**
     * Forget the cached settings for a store (call after persisting changes).
     */
    public function forget(int $storeId): void
    {
        unset($this->memo[$storeId]);
        Cache::forget(self::cacheKey($storeId));
    }

    /**
     * Resolve settings from the database for a store: defaults merged with the
     * published theme's stored overrides.
     *
     * @return array<string, mixed>
     */
    private function resolve(Store $store): array
    {
        $theme = Theme::query()
            ->where('store_id', $store->id)
            ->published()
            ->with('settings')
            ->latest('published_at')
            ->first();

        $stored = $theme?->settings?->settings_json ?? [];

        return array_replace_recursive(self::defaults(), $stored);
    }

    /**
     * The container-bound current store, or null when unbound.
     */
    private function currentStore(): ?Store
    {
        return app()->bound('current_store') ? app('current_store') : null;
    }

    /**
     * The cache key for a store's resolved theme settings.
     */
    private static function cacheKey(int $storeId): string
    {
        return "theme_settings:{$storeId}";
    }

    /**
     * The canonical default theme settings.
     *
     * These mirror the settings described in the storefront spec (announcement
     * bar, header behaviour, home section order, colours, footer, dark mode).
     * Stored settings are merged on top of this, so every key is always present.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'announcement' => [
                'enabled' => false,
                'text' => '',
                'link' => null,
                'background_color' => '#171717',
            ],
            'header' => [
                'sticky' => true,
                'logo_url' => null,
            ],
            'home' => [
                'sections' => [
                    ['type' => 'hero', 'enabled' => true],
                    ['type' => 'featured_collections', 'enabled' => true],
                    ['type' => 'featured_products', 'enabled' => true],
                    ['type' => 'newsletter', 'enabled' => true],
                    ['type' => 'rich_text', 'enabled' => false],
                ],
                'hero' => [
                    'heading' => 'Welcome to our store',
                    'subheading' => 'Discover our latest collection.',
                    'cta_label' => 'Shop now',
                    'cta_url' => '/collections',
                    'image_url' => null,
                ],
                'featured_collections' => [
                    'heading' => 'Shop by collection',
                    'handles' => [],
                    'limit' => 4,
                ],
                'featured_products' => [
                    'heading' => 'Featured products',
                    'collection_handle' => null,
                    'limit' => 8,
                ],
                'newsletter' => [
                    'heading' => 'Stay in the loop',
                    'subtext' => 'Subscribe for exclusive offers and updates.',
                ],
                'rich_text' => [
                    'html' => '',
                ],
            ],
            'colors' => [
                'primary' => '#2563eb',
                'secondary' => '#1e293b',
                'accent' => '#f59e0b',
            ],
            'typography' => [
                'scale' => 'base',
            ],
            'footer' => [
                'description' => '',
                'social' => [
                    'facebook' => null,
                    'instagram' => null,
                    'twitter' => null,
                    'tiktok' => null,
                    'youtube' => null,
                ],
                'payment_methods' => ['visa', 'mastercard', 'amex', 'paypal'],
            ],
            'dark_mode' => 'system',
        ];
    }
}
