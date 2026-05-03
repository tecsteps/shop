<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function forStore(Store $store): array
    {
        return Cache::remember($this->cacheKey($store), now()->addMinutes(5), function () use ($store): array {
            $theme = $this->publishedTheme($store);

            if ($theme === null) {
                return $this->defaultsForStore($store);
            }

            $settings = $theme->settings?->settings_json ?? [];

            return array_replace_recursive($this->defaultsForStore($store), $settings);
        });
    }

    public function publishedTheme(Store $store): ?Theme
    {
        return Theme::withoutGlobalScopes()
            ->with('settings')
            ->where('store_id', $store->getKey())
            ->where('status', ThemeStatus::Published)
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->first();
    }

    public function forget(Store $store): void
    {
        Cache::forget($this->cacheKey($store));
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultsForStore(Store $store): array
    {
        return [
            'announcement' => [
                'enabled' => true,
                'text' => "Free shipping on orders over 75.00 {$store->default_currency}",
                'url' => null,
            ],
            'header' => [
                'sticky' => true,
                'main_menu' => 'main-menu',
            ],
            'footer' => [
                'menu' => 'footer-menu',
                'tagline' => 'A self-contained demo storefront with scoped catalog data and checkout-ready products.',
            ],
            'home' => [
                'hero' => [
                    'eyebrow' => 'New season essentials',
                    'heading' => $store->name,
                    'subheading' => 'Browse a scoped demo catalog with variants, inventory states, sale pricing, and digital products.',
                    'primary_label' => 'Shop new arrivals',
                    'primary_url' => '/collections/new-arrivals',
                    'secondary_label' => 'View collections',
                    'secondary_url' => '/collections',
                ],
                'featured_product_limit' => 8,
                'featured_collection_limit' => 4,
            ],
        ];
    }

    private function cacheKey(Store $store): string
    {
        return "theme_settings:{$store->getKey()}";
    }
}
