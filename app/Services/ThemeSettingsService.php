<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    /** @var array<int, array<string, mixed>> */
    private array $loaded = [];

    /**
     * @return array<string, mixed>
     */
    public function getSettings(Store $store): array
    {
        if (isset($this->loaded[$store->id])) {
            return $this->loaded[$store->id];
        }

        $settings = Cache::remember(
            "theme_settings:{$store->id}",
            300,
            function () use ($store) {
                $theme = Theme::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('status', ThemeStatus::Published->value)
                    ->first();

                if (! $theme) {
                    return $this->defaults();
                }

                $themeSettings = $theme->settings;

                if (! $themeSettings) {
                    return $this->defaults();
                }

                return $themeSettings->settings_json;
            }
        );

        $this->loaded[$store->id] = $settings;

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $keys
     */
    public function get(Store $store, string $key, mixed $default = null): mixed
    {
        $settings = $this->getSettings($store);

        return data_get($settings, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'announcement_bar' => [
                'enabled' => false,
                'text' => '',
                'link' => null,
            ],
            'header' => [
                'sticky' => false,
                'logo_url' => null,
            ],
            'footer' => [
                'social_links' => [],
            ],
            'dark_mode' => 'system',
            'sections' => [
                'hero' => [
                    'enabled' => true,
                    'heading' => 'Welcome',
                    'subheading' => '',
                    'cta_text' => 'Shop now',
                    'cta_link' => '/collections',
                    'background_image' => null,
                ],
                'featured_collections' => ['enabled' => false, 'collection_ids' => []],
                'featured_products' => ['enabled' => false, 'product_ids' => []],
                'newsletter' => ['enabled' => false],
                'rich_text' => ['enabled' => false, 'content' => ''],
            ],
            'section_order' => ['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text'],
        ];
    }
}
