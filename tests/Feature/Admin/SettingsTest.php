<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Admin\Settings\Shipping as SettingsShipping;
use App\Livewire\Admin\Settings\Taxes as SettingsTaxes;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('saves general settings', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(SettingsIndex::class)
        ->set('name', 'Renamed Store')
        ->set('defaultCurrency', 'USD')
        ->set('defaultLocale', 'en')
        ->set('timezone', 'UTC')
        ->call('save');

    expect($store->fresh()->name)->toBe('Renamed Store')
        ->and($store->fresh()->default_currency)->toBe('USD');
});

it('creates a shipping zone with a rate', function (): void {
    [$user, $store] = loginAsAdmin();

    $component = Livewire::test(SettingsShipping::class)
        ->call('openZoneModal')
        ->set('zoneName', 'EU')
        ->set('zoneCountries', 'DE, AT, CH')
        ->call('createZone');

    $zone = ShippingZone::where('name', 'EU')->first();
    expect($zone)->not->toBeNull()
        ->and($zone->countries_json)->toBe(['DE', 'AT', 'CH']);

    $component
        ->call('openRateModal', $zone->id)
        ->set('rateName', 'Standard')
        ->set('rateType', 'flat')
        ->set('rateAmount', 599)
        ->call('createRate');

    $rate = ShippingRate::where('zone_id', $zone->id)->first();
    expect($rate)->not->toBeNull()
        ->and($rate->config_json['amount'])->toBe(599);
});

it('configures tax settings', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(SettingsTaxes::class)
        ->set('mode', 'manual')
        ->set('taxName', 'VAT')
        ->set('rateBasisPoints', 1900)
        ->set('pricesIncludeTax', true)
        ->call('save');

    $settings = TaxSettings::where('store_id', $store->id)->first();
    expect($settings)->not->toBeNull()
        ->and($settings->mode->value)->toBe('manual')
        ->and($settings->prices_include_tax)->toBeTrue()
        ->and($settings->config_json['rate_basis_points'])->toBe(1900)
        ->and($settings->config_json['name'])->toBe('VAT');
});
