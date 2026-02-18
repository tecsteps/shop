<?php

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;

it('creates a theme with store relationship', function () {
    $ctx = createStoreContext();

    $theme = Theme::factory()->create([
        'store_id' => $ctx['store']->id,
        'name' => 'Test Theme',
        'status' => ThemeStatus::Published,
    ]);

    expect($theme->store->id)->toBe($ctx['store']->id)
        ->and($theme->name)->toBe('Test Theme')
        ->and($theme->status)->toBe(ThemeStatus::Published);
});

it('scopes themes to current store', function () {
    $ctx = createStoreContext();
    Theme::factory()->count(2)->create(['store_id' => $ctx['store']->id]);

    $otherStore = \App\Models\Store::factory()->create(['organization_id' => $ctx['organization']->id]);
    Theme::factory()->count(3)->create(['store_id' => $otherStore->id]);

    expect(Theme::count())->toBe(2);
});

it('has theme files relationship', function () {
    $ctx = createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $ctx['store']->id]);

    ThemeFile::factory()->count(3)->create(['theme_id' => $theme->id]);

    expect($theme->files)->toHaveCount(3);
});

it('has theme settings relationship', function () {
    $ctx = createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $ctx['store']->id]);

    ThemeSettings::factory()->create(['theme_id' => $theme->id]);

    expect($theme->settings)->not->toBeNull()
        ->and($theme->settings->settings_json)->toBeArray();
});

it('casts theme status to enum', function () {
    $ctx = createStoreContext();
    $theme = Theme::factory()->published()->create(['store_id' => $ctx['store']->id]);

    expect($theme->status)->toBe(ThemeStatus::Published)
        ->and($theme->published_at)->not->toBeNull();
});
