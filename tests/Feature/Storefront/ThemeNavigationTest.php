<?php

use App\Models\NavigationMenu;
use App\Models\Store;
use App\Services\NavigationService;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->seed();
});

test('theme settings service loads the published theme settings', function () {
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

    $settings = app(ThemeSettingsService::class)->forStore($store);

    expect(data_get($settings, 'announcement.enabled'))->toBeTrue()
        ->and(data_get($settings, 'home.hero_heading'))->toBe('Acme Fashion')
        ->and(collect(app(ThemeSettingsService::class)->homeSections($settings))->pluck('key')->all())
        ->toBe(['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text']);
});

test('theme settings service normalizes legacy home section settings', function (): void {
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

    $settings = app(ThemeSettingsService::class)->mergeWithDefaults($store, [
        'home' => [
            'sections' => [
                ['key' => 'featured_products', 'enabled' => false],
                ['key' => 'hero', 'enabled' => true],
                ['key' => 'unknown', 'enabled' => true],
            ],
            'featured_products_count' => 99,
            'rich_text_html' => '<p>Safe<script>alert(1)</script></p>',
        ],
    ]);

    expect(app(ThemeSettingsService::class)->homeSections($settings))->toBe([
        ['key' => 'featured_products', 'enabled' => false],
        ['key' => 'hero', 'enabled' => true],
        ['key' => 'featured_collections', 'enabled' => true],
        ['key' => 'newsletter', 'enabled' => true],
        ['key' => 'rich_text', 'enabled' => true],
    ])
        ->and(data_get($settings, 'home.featured_products_count'))->toBe(8)
        ->and(data_get($settings, 'home.rich_text_html'))->toBe('<p>Safe</p>');
});

test('navigation service resolves seeded resource urls', function () {
    $menu = NavigationMenu::query()->where('handle', 'main-menu')->firstOrFail();

    $items = app(NavigationService::class)->buildTree($menu);

    expect($items)->toHaveCount(3)
        ->and($items[1]['url'])->toBe('/collections/summer-essentials')
        ->and($items[2]['url'])->toBe('/pages/about');
});
