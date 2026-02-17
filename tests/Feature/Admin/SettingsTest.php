<?php

use App\Enums\TaxMode;
use App\Livewire\Admin\Settings\Index;
use App\Livewire\Admin\Settings\Shipping;
use App\Livewire\Admin\Settings\Taxes;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use Livewire\Livewire;

it('renders the general settings page', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Settings');
});

it('saves general store settings', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Index::class)
        ->set('storeName', 'Updated Store Name')
        ->set('defaultCurrency', 'USD')
        ->set('defaultLocale', 'en')
        ->set('timezone', 'America/New_York')
        ->call('save')
        ->assertDispatched('toast');

    expect($ctx['store']->fresh()->name)->toBe('Updated Store Name');
    expect($ctx['store']->fresh()->default_currency)->toBe('USD');
});

it('creates a shipping zone', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Shipping::class)
        ->set('zoneName', 'Europe')
        ->set('zoneCountries', 'DE, FR, IT')
        ->call('saveZone')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('shipping_zones', [
        'store_id' => $ctx['store']->id,
        'name' => 'Europe',
    ]);
});

it('deletes a shipping zone', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $zone = ShippingZone::factory()->create(['store_id' => $ctx['store']->id]);

    Livewire::test(Shipping::class)
        ->call('deleteZone', $zone->id)
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('shipping_zones', ['id' => $zone->id]);
});

it('saves tax settings', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Taxes::class)
        ->set('mode', 'manual')
        ->set('pricesIncludeTax', true)
        ->set('rates', [['name' => 'VAT', 'rate' => '19']])
        ->call('save')
        ->assertDispatched('toast');

    $taxSettings = TaxSettings::where('store_id', $ctx['store']->id)->first();
    expect($taxSettings)->not->toBeNull();
    expect($taxSettings->mode)->toBe(TaxMode::Manual);
    expect($taxSettings->prices_include_tax)->toBeTrue();
});

it('adds and removes tax rates', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Taxes::class)
        ->assertSet('rates', [])
        ->call('addRate')
        ->assertCount('rates', 1)
        ->call('addRate')
        ->assertCount('rates', 2)
        ->call('removeRate', 0)
        ->assertCount('rates', 1);
});
