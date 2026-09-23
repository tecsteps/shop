<?php

use App\Livewire\Admin\Settings\Shipping;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

function shippingSettingsOwner(\App\Models\Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);

    return $user;
}

it('manages shipping zones, tiered rates, and address tests for the current store', function () {
    $store = shopStore();
    $user = shippingSettingsOwner($store);
    $component = Livewire::actingAs($user)->test(Shipping::class)
        ->set('zoneName', 'Berlin')
        ->set('countries', 'de')
        ->set('regions', 'DE-BE')
        ->call('saveZone')
        ->assertHasNoErrors();
    $zone = ShippingZone::query()->where('name', 'Berlin')->firstOrFail();

    expect($zone->countries)->toBe(['DE'])->and($zone->regions)->toBe(['DE-BE']);

    $component->set('zoneId', $zone->id)
        ->set('rateName', 'Parcel')
        ->set('rateType', 'weight')
        ->set('weightTiers', [
            ['min_weight_g' => 0, 'max_weight_g' => 500, 'price_amount' => 499],
            ['min_weight_g' => 501, 'max_weight_g' => '', 'price_amount' => 799],
        ])
        ->call('saveRate')
        ->assertHasNoErrors();

    $rate = $zone->rates()->firstOrFail();
    expect($rate->type)->toBe('weight')
        ->and($rate->config_json['tiers'])->toBe([
            ['min_weight_g' => 0, 'max_weight_g' => 500, 'price_amount' => 499],
            ['min_weight_g' => 501, 'max_weight_g' => null, 'price_amount' => 799],
        ]);

    $component->set('testCountryCode', 'DE')
        ->set('testRegionCode', 'BE')
        ->set('testCity', 'Berlin')
        ->set('testPostalCode', '10115')
        ->set('testWeightGrams', 250)
        ->call('testShippingAddress')
        ->assertHasNoErrors()
        ->assertSet('testResult.zone', 'Berlin')
        ->assertSet('testResult.rates.0.price_amount', 499);

    $component->set('testCountryCode', 'US')
        ->call('testShippingAddress')
        ->assertSet('testResult', null)
        ->assertSet('hasTestedAddress', true);
});

it('edits and deletes rates while preventing access to rates in another store', function () {
    $store = shopStore();
    $otherStore = shopStore('shipping-foreign.test');
    $user = shippingSettingsOwner($store);
    $zone = ShippingZone::factory()->create(['store_id' => $store->id, 'name' => 'Local']);
    $foreignZone = ShippingZone::factory()->create(['store_id' => $otherStore->id, 'name' => 'Foreign']);
    $rate = $zone->rates()->create(['name' => 'Economy', 'type' => 'flat', 'price_amount' => 500]);
    $foreignRate = $foreignZone->rates()->create(['name' => 'Foreign', 'type' => 'flat', 'price_amount' => 400]);
    expect(fn () => Livewire::actingAs($user)->test(Shipping::class)->call('editRate', $foreignRate->id))
        ->toThrow(ModelNotFoundException::class);

    $component = Livewire::actingAs($user)->test(Shipping::class);
    $component->call('editRate', $rate->id)
        ->set('rateName', 'Priority')
        ->set('rateAmount', 900)
        ->call('saveRate')
        ->assertHasNoErrors();

    expect($rate->fresh()->name)->toBe('Priority')
        ->and($rate->fresh()->price_amount)->toBe(900);

    $component->call('toggleRate', $rate->id)->assertHasNoErrors();
    expect($rate->fresh()->is_active)->toBeFalse();
    $component->call('deleteRate', $rate->id)->assertHasNoErrors();
    expect($zone->rates()->exists())->toBeFalse()
        ->and($foreignZone->rates()->count())->toBe(1);
});
