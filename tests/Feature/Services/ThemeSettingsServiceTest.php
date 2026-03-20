<?php

use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;

it('is registered as a singleton', function () {
    $instance1 = app(ThemeSettingsService::class);
    $instance2 = app(ThemeSettingsService::class);

    expect($instance1)->toBe($instance2);
});

it('loads published theme settings for a store', function () {
    $store = Store::factory()->create();
    $theme = Theme::factory()->published()->create(['store_id' => $store->id]);
    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'announcement_bar' => ['enabled' => true, 'text' => 'Free shipping over 50 EUR'],
        ],
    ]);

    $service = app(ThemeSettingsService::class);
    $settings = $service->getSettings($store);

    expect($settings['announcement_bar']['enabled'])->toBeTrue();
    expect($settings['announcement_bar']['text'])->toBe('Free shipping over 50 EUR');
});

it('returns defaults when no published theme exists', function () {
    $store = Store::factory()->create();
    Theme::factory()->create(['store_id' => $store->id, 'status' => 'draft']);

    $service = app(ThemeSettingsService::class);
    $settings = $service->getSettings($store);

    expect($settings['announcement_bar']['enabled'])->toBeFalse();
});

it('caches loaded settings in memory', function () {
    $store = Store::factory()->create();
    $theme = Theme::factory()->published()->create(['store_id' => $store->id]);
    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['dark_mode' => 'toggle'],
    ]);

    $service = app(ThemeSettingsService::class);
    $settings1 = $service->getSettings($store);
    $settings2 = $service->getSettings($store);

    expect($settings1)->toBe($settings2);
});

it('retrieves nested setting via get method', function () {
    $store = Store::factory()->create();
    $theme = Theme::factory()->published()->create(['store_id' => $store->id]);
    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'header' => ['sticky' => true, 'logo_url' => null],
        ],
    ]);

    $service = app(ThemeSettingsService::class);

    expect($service->get($store, 'header.sticky'))->toBeTrue();
    expect($service->get($store, 'header.logo_url'))->toBeNull();
    expect($service->get($store, 'nonexistent', 'fallback'))->toBe('fallback');
});
