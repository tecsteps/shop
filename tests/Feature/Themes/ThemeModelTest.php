<?php

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;

beforeEach(function () {
    $this->context = createStoreContext();
});

it('creates a theme with factory', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($theme)->toBeInstanceOf(Theme::class)
        ->and($theme->store_id)->toBe($this->context['store']->id)
        ->and($theme->status)->toBe(ThemeStatus::Published);
});

it('creates a draft theme', function () {
    $theme = Theme::factory()->draft()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($theme->status)->toBe(ThemeStatus::Draft)
        ->and($theme->published_at)->toBeNull();
});

it('has a store relationship', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    expect($theme->store->id)->toBe($this->context['store']->id);
});

it('has a files relationship', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    ThemeFile::factory()->create(['theme_id' => $theme->id]);

    expect($theme->files)->toHaveCount(1);
});

it('has a settings relationship', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    ThemeSettings::factory()->create(['theme_id' => $theme->id]);

    expect($theme->settings)->toBeInstanceOf(ThemeSettings::class);
});

it('scopes themes to current store', function () {
    Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    // Create a theme for another store (without full context to avoid hostname conflict)
    $otherStore = \App\Models\Store::factory()->create();
    Theme::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    // With current store bound, should only see one theme
    expect(Theme::count())->toBe(1);
});

it('stores theme settings as JSON', function () {
    $theme = Theme::factory()->create([
        'store_id' => $this->context['store']->id,
    ]);

    $settings = ThemeSettings::factory()->create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'primary_color' => '#ff0000',
            'hero_heading' => 'Test',
        ],
    ]);

    expect($settings->settings_json)->toBeArray()
        ->and($settings->get('primary_color'))->toBe('#ff0000')
        ->and($settings->get('hero_heading'))->toBe('Test')
        ->and($settings->get('nonexistent', 'default'))->toBe('default');
});
