<?php

namespace App\Services;

use App\Actions\SanitizeHtml;
use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;

class ThemeSettingsService
{
    public function __construct(
        private readonly SanitizeHtml $sanitizeHtml,
    ) {}

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

            return $this->mergeWithDefaults($store, $theme?->settings?->settings_json ?? []);
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function mergeWithDefaults(Store $store, array $settings): array
    {
        $merged = array_replace_recursive($this->defaults($store), $settings);
        $merged['home']['sections'] = $this->homeSections($settings);
        $merged['home']['featured_collections_count'] = $this->boundedInteger(
            data_get($merged, 'home.featured_collections_count'),
            3,
            2,
            4,
        );
        $merged['home']['featured_products_count'] = $this->boundedInteger(
            data_get($merged, 'home.featured_products_count'),
            6,
            4,
            8,
        );

        $richText = data_get($merged, 'home.rich_text_html');
        $merged['home']['rich_text_html'] = $richText === null
            ? null
            : ($this->sanitizeHtml)((string) $richText);

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function prepareForStorage(Store $store, array $settings): array
    {
        return $this->mergeWithDefaults($store, $settings);
    }

    /**
     * @return list<array{key: string, label: string, description: string}>
     */
    public function homeSectionDefinitions(): array
    {
        return [
            [
                'key' => 'hero',
                'label' => 'Hero banner',
                'description' => 'Primary homepage feature.',
            ],
            [
                'key' => 'featured_collections',
                'label' => 'Featured collections',
                'description' => 'Collection cards.',
            ],
            [
                'key' => 'featured_products',
                'label' => 'Featured products',
                'description' => 'Product grid.',
            ],
            [
                'key' => 'newsletter',
                'label' => 'Newsletter signup',
                'description' => 'Email capture block.',
            ],
            [
                'key' => 'rich_text',
                'label' => 'Rich text',
                'description' => 'Editorial content.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<array{key: string, enabled: bool}>
     */
    public function homeSections(array $settings): array
    {
        $sections = data_get($settings, 'home.sections');
        $definitions = collect($this->homeSectionDefinitions())->keyBy('key');
        $normalized = [];

        if (is_array($sections)) {
            foreach ($sections as $section) {
                $key = is_array($section) ? (string) ($section['key'] ?? '') : '';

                if (! $definitions->has($key) || array_key_exists($key, $normalized)) {
                    continue;
                }

                $normalized[$key] = [
                    'key' => $key,
                    'enabled' => $this->booleanValue(is_array($section) ? ($section['enabled'] ?? true) : true),
                ];
            }
        }

        foreach ($definitions->keys() as $key) {
            if (! array_key_exists($key, $normalized)) {
                $normalized[$key] = [
                    'key' => $key,
                    'enabled' => true,
                ];
            }
        }

        return array_values($normalized);
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
                'sections' => $this->homeSections([]),
                'hero_heading' => $store->name,
                'hero_subheading' => 'Curated products from '.$store->name.'.',
                'hero_cta_label' => 'Shop products',
                'hero_cta_url' => '/collections',
                'featured_collections_heading' => 'Featured Collections',
                'featured_collections_subheading' => 'Curated selections from '.$store->name.'.',
                'featured_collections_count' => 3,
                'featured_products_heading' => 'Featured Products',
                'featured_products_count' => 6,
                'newsletter_heading' => 'Stay in the loop',
                'newsletter_subheading' => 'Subscribe for exclusive offers and updates.',
                'rich_text_heading' => 'From the studio',
                'rich_text_html' => '<p>Discover thoughtful products selected for everyday use.</p>',
            ],
            'footer' => [
                'contact_email' => $store->organization?->billing_email,
            ],
        ];
    }

    private function boundedInteger(mixed $value, int $default, int $minimum, int $maximum): int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if ($integer === false) {
            $integer = $default;
        }

        return min($maximum, max($minimum, (int) $integer));
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $boolean = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $boolean ?? true;
    }
}
