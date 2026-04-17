<?php

use App\Models\Theme;
use App\Models\ThemeSettings;

it('belongs to a theme', function () {
    $settings = ThemeSettings::factory()->create();

    expect($settings->theme)->toBeInstanceOf(Theme::class);
});

it('casts settings_json to array', function () {
    $settings = ThemeSettings::factory()->create([
        'settings_json' => ['announcement_bar' => ['enabled' => true, 'text' => 'Hello']],
    ]);

    $settings->refresh();

    expect($settings->settings_json)->toBeArray();
    expect($settings->settings_json['announcement_bar']['enabled'])->toBeTrue();
});

it('uses theme_id as primary key', function () {
    $theme = Theme::factory()->create();
    $settings = ThemeSettings::factory()->create(['theme_id' => $theme->id]);

    expect($settings->getKeyName())->toBe('theme_id');
    expect($settings->getKey())->toBe($theme->id);
});

it('factory creates valid settings', function () {
    $settings = ThemeSettings::factory()->create();

    expect($settings->settings_json)->toBeArray();
    expect($settings->theme_id)->not->toBeNull();
});
