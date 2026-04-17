<?php

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

it('can create a theme', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'My Theme',
    ]);

    expect($theme)->toBeInstanceOf(Theme::class)
        ->and($theme->name)->toBe('My Theme')
        ->and($theme->status)->toBe(ThemeStatus::Draft);
});

it('can create a published theme', function () {
    $theme = Theme::factory()->published()->create([
        'store_id' => $this->store->id,
    ]);

    expect($theme->status)->toBe(ThemeStatus::Published)
        ->and($theme->published_at)->not->toBeNull();
});

it('has files relationship', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    ThemeFile::factory()->create([
        'theme_id' => $theme->id,
        'path' => 'layout.blade.php',
    ]);

    expect($theme->files)->toHaveCount(1)
        ->and($theme->files->first()->path)->toBe('layout.blade.php');
});

it('has theme settings relationship', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['color' => 'blue'],
    ]);

    expect($theme->themeSettings)->toBeInstanceOf(ThemeSettings::class)
        ->and($theme->themeSettings->settings_json)->toBe(['color' => 'blue']);
});

it('casts settings_json to array on ThemeSettings', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    $settings = ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['key' => 'value'],
    ]);

    expect($settings->settings_json)->toBeArray()
        ->and($settings->settings_json['key'])->toBe('value');
});

it('scopes themes by store', function () {
    $otherStore = Store::factory()->create();

    Theme::factory()->create(['store_id' => $this->store->id]);
    Theme::factory()->create(['store_id' => $otherStore->id]);

    expect(Theme::query()->count())->toBe(1);
});

it('loads active theme settings via service', function () {
    $theme = Theme::factory()->published()->create([
        'store_id' => $this->store->id,
    ]);

    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'sticky_header' => true,
            'colors' => ['primary' => '#ff0000'],
        ],
    ]);

    $service = app(ThemeSettingsService::class);

    expect($service->get('sticky_header'))->toBeTrue()
        ->and($service->get('colors.primary'))->toBe('#ff0000')
        ->and($service->get('nonexistent', 'default'))->toBe('default');
});

it('returns empty settings when no published theme exists', function () {
    $service = app(ThemeSettingsService::class);

    expect($service->all())->toBe([]);
});
