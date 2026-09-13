<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

/**
 * Active theme resolution and settings access for the storefront.
 */
class ThemeService
{
    public function activeTheme(Store $store): ?Theme
    {
        $themeId = Cache::remember(
            "theme_active:{$store->getKey()}",
            now()->addMinutes(5),
            fn (): ?int => Theme::query()
                ->where('store_id', $store->getKey())
                ->where('status', ThemeStatus::Published)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->value('id')
        );

        if ($themeId === null) {
            return null;
        }

        return Theme::query()->whereKey($themeId)->with('themeSetting')->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(Store $store): array
    {
        $theme = $this->activeTheme($store);
        $stored = $theme?->themeSetting?->settings_json ?? [];

        return array_replace_recursive($this->defaults($store), is_array($stored) ? $stored : []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(Store $store): array
    {
        return [
            'announcement' => [
                'show' => false,
                'text' => '',
                'link' => null,
            ],
            'header' => [
                'sticky' => false,
                'logo_url' => null,
            ],
            'hero' => [
                'show' => true,
                'heading' => 'Welcome to '.$store->name,
                'subheading' => '',
                'cta_text' => 'Shop now',
                'cta_url' => '/collections',
            ],
            'home' => [
                'featured_collections_count' => 4,
                'featured_products_count' => 8,
                'show_newsletter' => true,
                'rich_text_html' => null,
            ],
            'footer' => [
                'store_address' => null,
                'contact_email' => null,
            ],
            'social' => [],
            'dark_mode' => 'system',
        ];
    }
}
