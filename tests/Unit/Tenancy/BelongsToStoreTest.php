<?php

use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreSettings;
use Tests\TestCase;

uses(TestCase::class, \Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function (): void {
    app()->forgetInstance('current_store');
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

test('tenant models are created and queried for the current store only', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create();
    $otherStore = Store::factory()->for($organization)->create();

    app()->instance('current_store', $store);
    $settings = StoreSettings::create([
        'store_id' => $otherStore->getKey(),
        'settings_json' => ['store' => 'current'],
    ]);

    expect($settings->store_id)->toBe($store->getKey())
        ->and(StoreSettings::query()->pluck('store_id')->all())->toBe([$store->getKey()]);
});

test('tenant queries return no rows when no current store is resolved', function () {
    $organization = Organization::factory()->create();
    $store = Store::factory()->for($organization)->create();

    StoreSettings::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'settings_json' => ['store' => 'unresolved'],
    ]);

    expect(StoreSettings::query()->count())->toBe(0)
        ->and(StoreSettings::withoutGlobalScopes()->count())->toBe(1);
});
