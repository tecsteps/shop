<?php

use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->service = app(ThemeSettingsService::class);
    $this->service->reset();
});

it('loads settings for the active published theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['primary_color' => '#123456'],
    ]);

    expect($this->service->get('primary_color'))->toBe('#123456');
});

it('returns default value when setting is missing', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [],
    ]);

    expect($this->service->get('nonexistent', 'fallback'))->toBe('fallback');
});

it('returns null when no store is bound', function () {
    app()->forgetInstance('current_store');

    $service = new ThemeSettingsService;

    expect($service->load())->toBeNull()
        ->and($service->get('anything', 'default'))->toBe('default');
});

it('returns null when no published theme exists', function () {
    Theme::factory()->draft()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($this->service->load())->toBeNull();
});

it('returns all settings as array', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['key1' => 'value1', 'key2' => 'value2'],
    ]);

    $all = $this->service->all();

    expect($all)->toBeArray()
        ->and($all['key1'])->toBe('value1')
        ->and($all['key2'])->toBe('value2');
});
