<?php

use App\Models\ShippingZone;
use App\Models\StoreDomain;
use App\Models\TaxSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->actingAs($this->user);
    session()->put('current_store_id', $this->store->id);
});

it('renders settings page for owner', function () {
    $response = $this->get('/admin/settings');

    $response->assertSuccessful();
    $response->assertSee('Settings');
});

it('denies settings access for staff role', function () {
    $staffUser = \App\Models\User::factory()->create();
    $this->store->users()->attach($staffUser, ['role' => 'staff']);
    $this->actingAs($staffUser);

    $response = $this->get('/admin/settings');

    $response->assertForbidden();
});

it('saves general store settings', function () {
    Livewire::test(\App\Livewire\Admin\Settings\Index::class)
        ->set('storeName', 'Updated Store Name')
        ->set('defaultCurrency', 'EUR')
        ->set('defaultLocale', 'de')
        ->set('timezone', 'Europe/Berlin')
        ->call('saveGeneral')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $this->store->refresh();
    expect($this->store->name)->toBe('Updated Store Name')
        ->and($this->store->default_currency)->toBe('EUR')
        ->and($this->store->default_locale)->toBe('de')
        ->and($this->store->timezone)->toBe('Europe/Berlin');
});

it('adds a new domain', function () {
    Livewire::test(\App\Livewire\Admin\Settings\Index::class)
        ->set('newHostname', 'custom.example.com')
        ->set('newDomainType', 'storefront')
        ->call('addDomain')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(StoreDomain::where('hostname', 'custom.example.com')->exists())->toBeTrue();
});

it('creates a shipping zone with rates', function () {
    Livewire::test(\App\Livewire\Admin\Settings\Index::class)
        ->set('zoneName', 'US Domestic')
        ->set('zoneCountries', 'US, CA')
        ->call('saveZone')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $zone = ShippingZone::where('store_id', $this->store->id)
        ->where('name', 'US Domestic')
        ->first();
    expect($zone)->not->toBeNull()
        ->and($zone->countries_json)->toContain('US');
});

it('saves tax settings', function () {
    Livewire::test(\App\Livewire\Admin\Settings\Index::class)
        ->set('tab', 'taxes')
        ->set('taxMode', 'manual')
        ->set('taxRate', 19)
        ->set('taxName', 'VAT')
        ->set('pricesIncludeTax', true)
        ->call('saveTax')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $tax = TaxSettings::where('store_id', $this->store->id)->first();
    expect($tax)->not->toBeNull()
        ->and($tax->rate)->toBe(19)
        ->and($tax->tax_name)->toBe('VAT')
        ->and($tax->prices_include_tax)->toBeTrue();
});
