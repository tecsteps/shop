<?php

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;

it('creates a theme with factory', function () {
    $context = createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $context['store']->id]);

    expect($theme)->toBeInstanceOf(Theme::class)
        ->and($theme->store_id)->toBe($context['store']->id)
        ->and($theme->status)->toBe(ThemeStatus::Draft);
});

it('creates a published theme', function () {
    $context = createStoreContext();
    $theme = Theme::factory()->published()->create(['store_id' => $context['store']->id]);

    expect($theme->status)->toBe(ThemeStatus::Published)
        ->and($theme->published_at)->not->toBeNull();
});

it('has files relationship', function () {
    $context = createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $context['store']->id]);
    ThemeFile::factory()->create(['theme_id' => $theme->id]);

    expect($theme->files)->toHaveCount(1)
        ->and($theme->files->first())->toBeInstanceOf(ThemeFile::class);
});

it('has settings relationship', function () {
    $context = createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $context['store']->id]);
    ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => ['announcement_bar_enabled' => true],
    ]);

    expect($theme->settings)->toBeInstanceOf(ThemeSettings::class)
        ->and($theme->settings->settings_json)->toBe(['announcement_bar_enabled' => true]);
});

it('belongs to store', function () {
    $context = createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $context['store']->id]);

    expect($theme->store->id)->toBe($context['store']->id);
});

it('casts status to enum', function () {
    $context = createStoreContext();
    $theme = Theme::factory()->create(['store_id' => $context['store']->id, 'status' => 'published']);

    expect($theme->status)->toBe(ThemeStatus::Published);
});
