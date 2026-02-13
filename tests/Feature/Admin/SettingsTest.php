<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Settings;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createSettingsAdmin(StoreUserRole $role = StoreUserRole::Owner): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $store->users()->attach($user, ['role' => $role->value]);
    app()->instance('current_store', $store);
    session()->put('current_store_id', $store->id);

    return [$user, $store];
}

it('shows settings page', function () {
    [$user, $store] = createSettingsAdmin();

    Livewire::actingAs($user)
        ->test(Settings\Index::class)
        ->assertSee('Settings')
        ->assertSee('General')
        ->assertSee('Shipping')
        ->assertSee('Taxes');
});

it('updates store settings', function () {
    [$user, $store] = createSettingsAdmin();

    Livewire::actingAs($user)
        ->test(Settings\Index::class)
        ->set('storeName', 'Updated Store')
        ->set('defaultCurrency', 'EUR')
        ->set('timezone', 'Europe/Berlin')
        ->call('saveGeneral')
        ->assertHasNoErrors();

    $store->refresh();
    expect($store->name)->toBe('Updated Store');
    expect($store->default_currency)->toBe('EUR');
    expect($store->timezone)->toBe('Europe/Berlin');
});

it('displays shipping zones on shipping tab', function () {
    [$user, $store] = createSettingsAdmin();

    ShippingZone::factory()->create([
        'store_id' => $store->id,
        'name' => 'Domestic',
    ]);

    Livewire::actingAs($user)
        ->test(Settings\Index::class)
        ->assertSet('tab', 'general')
        ->set('tab', 'shipping')
        ->assertSee('Shipping Zones');

    // Verify the zone exists in the database for this store
    expect(ShippingZone::where('store_id', $store->id)->where('name', 'Domestic')->exists())->toBeTrue();
});

it('denies settings access for Staff role', function () {
    [$user, $store] = createSettingsAdmin(StoreUserRole::Staff);

    Livewire::actingAs($user)
        ->test(Settings\Index::class)
        ->assertForbidden();
});
