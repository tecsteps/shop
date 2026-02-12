<?php

use App\Livewire\Admin\Settings\General;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function settingsGeneralAdmin(): array
{
    $store = Store::factory()->create([
        'name' => 'My Test Store',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'Europe/Berlin',
    ]);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the general settings page', function () {
    [$user, $store] = settingsGeneralAdmin();

    Livewire::actingAs($user)
        ->test(General::class)
        ->assertSee('General')
        ->assertSuccessful();
});

test('it pre-populates settings from store', function () {
    [$user, $store] = settingsGeneralAdmin();

    Livewire::actingAs($user)
        ->test(General::class)
        ->assertSet('storeName', 'My Test Store')
        ->assertSet('defaultCurrency', 'EUR')
        ->assertSet('defaultLocale', 'en')
        ->assertSet('timezone', 'Europe/Berlin');
});

test('it saves general settings', function () {
    [$user, $store] = settingsGeneralAdmin();

    Livewire::actingAs($user)
        ->test(General::class)
        ->set('storeName', 'Updated Store Name')
        ->set('defaultCurrency', 'USD')
        ->set('timezone', 'America/New_York')
        ->call('save')
        ->assertDispatched('toast');

    $store->refresh();

    expect($store->name)->toBe('Updated Store Name')
        ->and($store->default_currency)->toBe('USD')
        ->and($store->timezone)->toBe('America/New_York');
});

test('it validates required store name', function () {
    [$user, $store] = settingsGeneralAdmin();

    Livewire::actingAs($user)
        ->test(General::class)
        ->set('storeName', '')
        ->call('save')
        ->assertHasErrors(['storeName']);
});
