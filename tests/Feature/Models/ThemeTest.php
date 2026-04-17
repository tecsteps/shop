<?php

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;

it('belongs to a store', function () {
    $theme = Theme::factory()->create();

    expect($theme->store)->toBeInstanceOf(Store::class);
});

it('has many theme files', function () {
    $theme = Theme::factory()->create();
    ThemeFile::factory()->count(3)->create(['theme_id' => $theme->id]);

    expect($theme->files)->toHaveCount(3);
});

it('has one theme settings', function () {
    $theme = Theme::factory()->create();
    ThemeSettings::factory()->create(['theme_id' => $theme->id]);

    expect($theme->settings)->toBeInstanceOf(ThemeSettings::class);
});

it('casts status to ThemeStatus enum', function () {
    $theme = Theme::factory()->published()->create();

    expect($theme->status)->toBeInstanceOf(ThemeStatus::class);
    expect($theme->status)->toBe(ThemeStatus::Published);
});

it('factory creates valid theme', function () {
    $theme = Theme::factory()->create();

    expect($theme->name)->not->toBeEmpty();
    expect($theme->status)->toBe(ThemeStatus::Draft);
    expect($theme->store_id)->not->toBeNull();
});

it('cascades delete to theme when store is deleted', function () {
    $store = Store::factory()->create();
    $theme = Theme::factory()->create(['store_id' => $store->id]);

    $store->delete();

    expect(Theme::find($theme->id))->toBeNull();
});

it('cascades delete to files and settings when theme is deleted', function () {
    $theme = Theme::factory()->create();
    ThemeFile::factory()->count(3)->create(['theme_id' => $theme->id]);
    ThemeSettings::factory()->create(['theme_id' => $theme->id]);

    $themeId = $theme->id;
    $theme->delete();

    expect(ThemeFile::where('theme_id', $themeId)->count())->toBe(0);
    expect(ThemeSettings::where('theme_id', $themeId)->count())->toBe(0);
});
