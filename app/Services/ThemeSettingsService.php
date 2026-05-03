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
        return Cache::remember("theme_settings:{$store->id}", now()->addMinutes(5), function () use ($store): array {
            $theme = Theme::withoutGlobalScopes()
                ->with('settings')
                ->where('store_id', $store->id)
                ->where('status', ThemeStatus::Published)
                ->latest('published_at')
                ->first();

            return array_replace_recursive($this->defaults($store), $theme?->settings?->settings_json ?? []);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(Store $store): array
    {
        return [
            'announcement' => [
                'enabled' => false,
                'text' => '',
                'link' => null,
            ],
            'home' => [
                'hero_heading' => $store->name,
                'hero_subheading' => 'Curated products from '.$store->name.'.',
                'hero_cta_label' => 'Shop products',
                'hero_cta_url' => '/collections',
            ],
            'footer' => [
                'contact_email' => $store->organization?->billing_email,
            ],
        ];
    }
}
