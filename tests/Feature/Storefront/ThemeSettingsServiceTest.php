<?php

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = new ThemeSettingsService;
});

test('returns defaults when no theme exists', function () {
    $settings = $this->service->all();

    expect($settings)->toBeArray()
        ->and($settings['sticky_header'])->toBeFalse()
        ->and($settings['dark_mode'])->toBe('system');
});

test('returns merged settings when theme exists', function () {
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'sticky_header' => true,
            'colors' => ['primary' => '#ff0000'],
        ],
    ]);

    $settings = $this->service->all();

    expect($settings['sticky_header'])->toBeTrue()
        ->and($settings['colors']['primary'])->toBe('#ff0000')
        ->and($settings['colors']['secondary'])->toBe('#6b7280');
});

test('get returns specific key with dot notation', function () {
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'hero' => ['heading' => 'Custom Heading'],
        ],
    ]);

    expect($this->service->get('hero.heading'))->toBe('Custom Heading');
});

test('get returns default for missing key', function () {
    expect($this->service->get('nonexistent.key', 'default'))->toBe('default');
});

test('ignores draft themes', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->store->id,
        'status' => ThemeStatus::Draft,
    ]);
    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => ['sticky_header' => true],
    ]);

    $settings = $this->service->all();

    expect($settings['sticky_header'])->toBeFalse();
});

test('returns defaults when no store is bound', function () {
    app()->forgetInstance('current_store');

    $service = new ThemeSettingsService;
    $settings = $service->all();

    expect($settings)->toBeArray()
        ->and($settings['dark_mode'])->toBe('system');
});
