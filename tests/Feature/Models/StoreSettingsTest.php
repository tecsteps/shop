<?php

use App\Models\Store;
use App\Models\StoreSettings;

it('belongs to a store', function () {
    $settings = StoreSettings::factory()->create();

    expect($settings->store)->toBeInstanceOf(Store::class);
});

it('casts settings_json to array', function () {
    $settings = StoreSettings::factory()->create([
        'settings_json' => ['key' => 'value'],
    ]);

    $settings->refresh();

    expect($settings->settings_json)->toBeArray();
    expect($settings->settings_json['key'])->toBe('value');
});

it('factory creates valid records', function () {
    $settings = StoreSettings::factory()->create();

    expect($settings->settings_json)->toBeArray();
    expect($settings->settings_json)->not->toBeEmpty();
});
