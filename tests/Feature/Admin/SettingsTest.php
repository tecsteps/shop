<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Settings\Domains;
use App\Livewire\Admin\Settings\General;
use App\Livewire\Admin\Settings\Shipping;
use App\Livewire\Admin\Settings\Taxes;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
});

it('renders the settings page', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $this->get('/admin/settings')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('General');
});

it('updates general store settings', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Livewire::test(General::class)
        ->set('storeName', 'Renamed Store')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->store->fresh()->name)->toBe('Renamed Store');
});

it('configures shipping zones', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Livewire::test(Shipping::class)
        ->call('openZoneModal')
        ->set('zoneName', 'Domestic')
        ->set('zoneCountriesInput', 'US, CA')
        ->call('saveZone')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shipping_zones', ['store_id' => $this->store->id, 'name' => 'Domestic']);

    $zone = $this->store->refresh() ?? null;
    $zoneId = App\Models\ShippingZone::where('name', 'Domestic')->value('id');

    Livewire::test(Shipping::class)
        ->call('openRateModal', $zoneId)
        ->set('rateName', 'Standard')
        ->set('rateType', 'flat')
        ->set('rateConfig.price', '5.00')
        ->call('saveRate')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shipping_rates', ['zone_id' => $zoneId, 'name' => 'Standard']);
});

it('configures tax settings', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Livewire::test(Taxes::class)
        ->set('mode', 'manual')
        ->set('manualRates.0.zone_name', 'EU')
        ->set('manualRates.0.rate_percentage', '19.00')
        ->set('pricesIncludeTax', true)
        ->call('save')
        ->assertHasNoErrors();

    $settings = App\Models\TaxSettings::find($this->store->id);
    expect($settings->mode->value)->toBe('manual');
    expect($settings->prices_include_tax)->toBeTrue();
    expect($settings->defaultRateBasisPoints())->toBe(1900);
});

it('restricts settings to owner and admin roles', function (): void {
    $staff = User::factory()->create();
    $this->store->users()->attach($staff->id, ['role' => StoreUserRole::Staff->value]);
    actingAsAdmin($staff, $this->store);

    $this->get('/admin/settings')->assertForbidden();
});

it('manages store domains', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Livewire::test(Domains::class)
        ->set('newHostname', 'new-shop.test')
        ->set('newType', 'storefront')
        ->call('addDomain')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('store_domains', ['store_id' => $this->store->id, 'hostname' => 'new-shop.test']);
});
