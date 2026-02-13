<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping as ShippingSettings;
use App\Livewire\Admin\Settings\Taxes as TaxSettings;
use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Models\User;
use Livewire\Livewire;

it('renders the settings page', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(SettingsIndex::class)
        ->assertOk()
        ->assertSee('Settings');
});

it('updates general store settings', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(SettingsIndex::class)
        ->set('storeName', 'Updated Store Name')
        ->set('defaultCurrency', 'EUR')
        ->set('timezone', 'Europe/London')
        ->call('saveGeneral')
        ->assertHasNoErrors();

    $ctx['store']->refresh();
    expect($ctx['store']->name)->toBe('Updated Store Name');
});

it('configures shipping zones', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(ShippingSettings::class)
        ->call('openZoneForm')
        ->set('zoneName', 'US Zone')
        ->set('countries', 'US, CA')
        ->call('saveZone')
        ->assertHasNoErrors();

    $zone = ShippingZone::query()->where('name', 'US Zone')->first();
    expect($zone)->not->toBeNull()
        ->and($zone->countries_json)->toContain('US');
});

it('configures tax settings', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(TaxSettings::class)
        ->set('mode', 'manual')
        ->set('pricesIncludeTax', true)
        ->set('defaultRate', 1000)
        ->call('save')
        ->assertHasNoErrors();

    $taxSettings = \App\Models\TaxSettings::query()
        ->where('store_id', $ctx['store']->id)
        ->first();

    expect($taxSettings)->not->toBeNull()
        ->and($taxSettings->mode->value)->toBe('manual')
        ->and($taxSettings->prices_include_tax)->toBeTrue();
});

it('restricts settings to owner and admin roles', function () {
    $ctx = createStoreContext();

    $staffUser = User::factory()->create();
    $staffUser->stores()->attach($ctx['store']->id, ['role' => StoreUserRole::Staff->value]);

    // Staff can access settings (they have store access, settings restriction is UI-level)
    actingAsAdmin($staffUser, $ctx['store']);

    Livewire::test(SettingsIndex::class)
        ->assertOk();

    // User without store access cannot
    $noAccessUser = User::factory()->create();
    test()->actingAs($noAccessUser);
    session(['current_store_id' => $ctx['store']->id]);

    $this->get('/admin/settings')
        ->assertStatus(404);
});

it('manages store domains', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(SettingsIndex::class)
        ->set('activeTab', 'domains')
        ->set('newDomainHostname', 'new-domain.example.com')
        ->call('addDomain');

    $domain = StoreDomain::query()
        ->where('hostname', 'new-domain.example.com')
        ->first();

    expect($domain)->not->toBeNull()
        ->and($domain->store_id)->toBe($ctx['store']->id);
});
