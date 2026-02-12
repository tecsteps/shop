<?php

use App\Livewire\Admin\Settings\Taxes;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function settingsTaxesAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the taxes settings page', function () {
    [$user, $store] = settingsTaxesAdmin();

    Livewire::actingAs($user)
        ->test(Taxes::class)
        ->assertSee('Tax')
        ->assertSuccessful();
});

test('it pre-populates from existing tax settings', function () {
    [$user, $store] = settingsTaxesAdmin();

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'mode' => 'manual',
        'prices_include_tax' => true,
        'tax_rate_basis_points' => 1900,
        'tax_name' => 'VAT',
        'shipping_taxable' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Taxes::class)
        ->assertSet('mode', 'manual')
        ->assertSet('pricesIncludeTax', true)
        ->assertSet('taxRateBasisPoints', 1900)
        ->assertSet('taxName', 'VAT')
        ->assertSet('shippingTaxable', true);
});

test('it saves tax settings', function () {
    [$user, $store] = settingsTaxesAdmin();

    Livewire::actingAs($user)
        ->test(Taxes::class)
        ->set('mode', 'manual')
        ->set('taxRateBasisPoints', 2000)
        ->set('taxName', 'Sales Tax')
        ->set('pricesIncludeTax', false)
        ->set('shippingTaxable', true)
        ->call('save')
        ->assertDispatched('toast');

    $tax = TaxSettings::where('store_id', $store->id)->first();

    expect($tax)->not->toBeNull()
        ->and($tax->tax_rate_basis_points)->toBe(2000)
        ->and($tax->tax_name)->toBe('Sales Tax')
        ->and($tax->prices_include_tax)->toBeFalse()
        ->and($tax->shipping_taxable)->toBeTrue();
});

test('it updates existing tax settings via upsert', function () {
    [$user, $store] = settingsTaxesAdmin();

    TaxSettings::factory()->create([
        'store_id' => $store->id,
        'tax_rate_basis_points' => 1900,
    ]);

    Livewire::actingAs($user)
        ->test(Taxes::class)
        ->set('taxRateBasisPoints', 700)
        ->set('taxName', 'GST')
        ->call('save')
        ->assertDispatched('toast');

    $tax = TaxSettings::where('store_id', $store->id)->first();

    expect($tax->tax_rate_basis_points)->toBe(700)
        ->and($tax->tax_name)->toBe('GST');
});

test('it validates basis points max is 10000', function () {
    [$user, $store] = settingsTaxesAdmin();

    Livewire::actingAs($user)
        ->test(Taxes::class)
        ->set('taxRateBasisPoints', 15000)
        ->call('save')
        ->assertHasErrors(['taxRateBasisPoints']);
});

test('it defaults to empty state when no tax settings exist', function () {
    [$user, $store] = settingsTaxesAdmin();

    Livewire::actingAs($user)
        ->test(Taxes::class)
        ->assertSet('mode', 'manual')
        ->assertSet('taxRateBasisPoints', 0)
        ->assertSet('taxName', '')
        ->assertSet('pricesIncludeTax', false)
        ->assertSet('shippingTaxable', false);
});
