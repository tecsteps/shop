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
        ->and(data_get($settings, 'home.hero_heading'))->toBe('Acme Fashion');
});

test('navigation service resolves seeded resource urls', function () {
    $menu = NavigationMenu::query()->where('handle', 'main-menu')->firstOrFail();

    $items = app(NavigationService::class)->buildTree($menu);

    expect($items)->toHaveCount(3)
        ->and($items[1]['url'])->toBe('/collections/summer-essentials')
        ->and($items[2]['url'])->toBe('/pages/about');
});
