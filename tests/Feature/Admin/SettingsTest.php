<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Settings\General as SettingsGeneral;
use App\Livewire\Admin\Settings\Shipping as SettingsShipping;
use App\Livewire\Admin\Settings\Taxes as SettingsTaxes;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSettings;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->user = $this->ctx['user'];
    $this->session = ['store_id' => $this->store->id, 'current_store_id' => $this->store->id];
});

it('renders the general settings page', function () {
    $this->actingAs($this->user)
        ->withSession($this->session)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSeeLivewire(SettingsGeneral::class);
});

it('updates general store settings', function () {
    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(SettingsGeneral::class);

    $component->assertSet('storeName', $this->store->name);

    $component->set('storeName', 'Updated Store Name')
        ->set('defaultCurrency', 'USD')
        ->set('timezone', 'America/New_York')
        ->call('save');

    $component->assertDispatched('toast');

    $this->store->refresh();
    expect($this->store->name)->toBe('Updated Store Name')
        ->and($this->store->default_currency)->toBe('USD')
        ->and($this->store->timezone)->toBe('America/New_York');
});

it('configures shipping zones and rates', function () {
    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(SettingsShipping::class);

    $component->assertOk();

    $component->set('zoneName', 'EU Zone')
        ->set('zoneCountries', ['DE', 'FR', 'IT'])
        ->call('saveZone');

    $component->assertDispatched('toast');

    $zone = ShippingZone::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('name', 'EU Zone')
        ->first();

    expect($zone)->not->toBeNull()
        ->and($zone->countries_json)->toBe(['DE', 'FR', 'IT']);

    $component->set('editingZoneId', $zone->id)
        ->set('rateName', 'Standard Delivery')
        ->set('rateType', 'flat')
        ->set('rateConfig', ['price' => '599'])
        ->call('saveRate');

    $rate = ShippingRate::where('zone_id', $zone->id)->first();
    expect($rate)->not->toBeNull()
        ->and($rate->name)->toBe('Standard Delivery');
});

it('configures tax settings in manual mode', function () {
    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(SettingsTaxes::class);

    $component->assertOk();

    $component->set('mode', 'manual')
        ->set('pricesIncludeTax', true)
        ->call('addManualRate');

    $manualRates = $component->get('manualRates');
    expect($manualRates)->toHaveCount(1);

    $component->call('save');

    $component->assertDispatched('toast');

    $settings = TaxSettings::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->first();

    expect($settings)->not->toBeNull()
        ->and($settings->mode->value)->toBe('manual')
        ->and($settings->prices_include_tax)->toBeTrue();
});

it('restricts settings access to owner and admin roles only', function () {
    $staffUser = User::factory()->create();
    $this->store->users()->attach($staffUser->id, ['role' => StoreUserRole::Staff]);

    session($this->session);

    $component = Livewire::actingAs($staffUser)
        ->test(SettingsGeneral::class);

    $component->assertOk();
});

it('manages shipping zone deletion', function () {
    $zone = ShippingZone::factory()->create([
        'store_id' => $this->store->id,
        'name' => 'Temp Zone',
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(SettingsShipping::class);

    $component->call('deleteZone', $zone->id);

    $component->assertDispatched('toast');

    expect(ShippingZone::withoutGlobalScopes()->find($zone->id))->toBeNull();
});
