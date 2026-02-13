<?php

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

test('theme belongs to store', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    expect($theme->store->id)->toBe($this->store->id);
});

test('theme has many files', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);
    ThemeFile::factory()->count(3)->create(['theme_id' => $theme->id]);

    expect($theme->files)->toHaveCount(3);
});

test('theme has one settings', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);
    ThemeSettings::factory()->create(['theme_id' => $theme->id]);

    expect($theme->settings)->not->toBeNull()
        ->and($theme->settings->theme_id)->toBe($theme->id);
});

test('theme casts status to enum', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->store->id,
        'status' => ThemeStatus::Published,
    ]);

    expect($theme->status)->toBe(ThemeStatus::Published);
});

test('theme factory creates published state', function () {
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);

    expect($theme->status)->toBe(ThemeStatus::Published)
        ->and($theme->published_at)->not->toBeNull();
});

test('theme settings stores json correctly', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);
    $settingsData = ['primary_color' => '#ff0000', 'sticky_header' => true];

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => $settingsData,
    ]);

    $settings = ThemeSettings::find($theme->id);
    expect($settings->settings_json)->toBe($settingsData);
});
