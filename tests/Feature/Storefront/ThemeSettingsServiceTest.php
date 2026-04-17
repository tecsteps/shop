<?php

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;

it('returns empty settings when no store is bound', function () {
    $service = app(ThemeSettingsService::class);

    expect($service->get())->toBe([]);
});

it('returns empty settings when no published theme exists', function () {
    $ctx = createStoreContext();
    $service = app(ThemeSettingsService::class);

    Theme::factory()->create([
        'store_id' => $ctx['store']->id,
        'status' => ThemeStatus::Draft,
    ]);

    expect($service->get())->toBe([]);
});

it('returns settings from the published theme', function () {
    $ctx = createStoreContext();

    $theme = Theme::factory()->published()->create([
        'store_id' => $ctx['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['test_key' => 'test_value'],
    ]);

    $service = app(ThemeSettingsService::class);
    $settings = $service->get();

    expect($settings)->toBe(['test_key' => 'test_value']);
});

it('retrieves individual settings via dot notation', function () {
    $ctx = createStoreContext();

    $theme = Theme::factory()->published()->create([
        'store_id' => $ctx['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'announcement_bar' => [
                'enabled' => true,
                'text' => 'Hello World',
            ],
        ],
    ]);

    $service = app(ThemeSettingsService::class);

    expect($service->setting('announcement_bar.enabled'))->toBeTrue()
        ->and($service->setting('announcement_bar.text'))->toBe('Hello World')
        ->and($service->setting('nonexistent', 'default'))->toBe('default');
});

it('caches settings and clears cache', function () {
    $ctx = createStoreContext();

    $theme = Theme::factory()->published()->create([
        'store_id' => $ctx['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['key' => 'original'],
    ]);

    $service = app(ThemeSettingsService::class);
    $settings = $service->get();

    expect($settings['key'])->toBe('original');

    $service->clearCache($ctx['store']->id);
});
