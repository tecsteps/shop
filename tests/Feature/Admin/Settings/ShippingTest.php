<?php

use App\Livewire\Admin\Settings\Shipping;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function settingsShippingAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the shipping settings page', function () {
    [$user, $store] = settingsShippingAdmin();

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->assertSee('Shipping')
        ->assertSuccessful();
});

test('it lists shipping zones for the current store', function () {
    [$user, $store] = settingsShippingAdmin();

    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'Germany Zone',
    ]);

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->assertSee('Germany Zone');
});

test('it creates a new shipping zone', function () {
    [$user, $store] = settingsShippingAdmin();

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->call('openZoneModal')
        ->assertSet('showZoneModal', true)
        ->set('zoneName', 'Europe Zone')
        ->call('saveZone')
        ->assertSet('showZoneModal', false)
        ->assertDispatched('toast');

    expect(ShippingZone::where('store_id', $store->id)->where('name', 'Europe Zone')->exists())->toBeTrue();
});

test('it edits an existing shipping zone', function () {
    [$user, $store] = settingsShippingAdmin();

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'Old Name',
    ]);

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->call('openZoneModal', $zone->id)
        ->assertSet('zoneName', 'Old Name')
        ->set('zoneName', 'New Name')
        ->call('saveZone')
        ->assertDispatched('toast');

    $zone->refresh();
    expect($zone->name)->toBe('New Name');
});

test('it deletes a shipping zone', function () {
    [$user, $store] = settingsShippingAdmin();

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->call('deleteZone', $zone->id)
        ->assertDispatched('toast');

    expect(ShippingZone::find($zone->id))->toBeNull();
});

test('it creates a new shipping rate for a zone', function () {
    [$user, $store] = settingsShippingAdmin();

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->call('openRateModal', $zone->id)
        ->assertSet('showRateModal', true)
        ->set('rateName', 'Standard Delivery')
        ->set('rateType', 'flat')
        ->set('rateAmount', '4.99')
        ->call('saveRate')
        ->assertSet('showRateModal', false)
        ->assertDispatched('toast');

    $rate = ShippingRate::where('zone_id', $zone->id)->first();

    expect($rate)->not->toBeNull()
        ->and($rate->name)->toBe('Standard Delivery')
        ->and($rate->config_json['amount'])->toBe(499);
});

test('it deletes a shipping rate', function () {
    [$user, $store] = settingsShippingAdmin();

    $zone = ShippingZone::factory()->create([
        'store_id' => $store->id,
    ]);

    $rate = ShippingRate::factory()->create([
        'zone_id' => $zone->id,
    ]);

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->call('deleteRate', $rate->id)
        ->assertDispatched('toast');

    expect(ShippingRate::find($rate->id))->toBeNull();
});

test('it validates zone name is required', function () {
    [$user, $store] = settingsShippingAdmin();

    Livewire::actingAs($user)
        ->test(Shipping::class)
        ->call('openZoneModal')
        ->set('zoneName', '')
        ->call('saveZone')
        ->assertHasErrors(['zoneName']);
});
