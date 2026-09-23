<?php

use App\Livewire\Admin\Settings\Taxes;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\TaxCalculator;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;

function taxSettingsOwner(\App\Models\Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);

    return $user;
}

it('saves multiple country and region tax rates as integer basis points', function () {
    $store = shopStore();
    $user = taxSettingsOwner($store);

    Livewire::actingAs($user)->test(Taxes::class)
        ->set('mode', 'manual')
        ->set('pricesIncludeTax', false)
        ->set('defaultRatePercentage', '20.00')
        ->set('manualRates', [
            ['zone_name' => 'DE', 'rate_percentage' => '19.00'],
            ['zone_name' => 'US-CA', 'rate_percentage' => '7.25'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $settings = TaxSetting::query()->where('store_id', $store->id)->firstOrFail();
    expect($settings->mode)->toBe('manual')
        ->and($settings->defaultTaxRateForCalculator())->toBe(2000)
        ->and($settings->prices_include_tax)->toBeFalse()
        ->and($settings->config_json['tax_rates'])->toBe([
            ['country_code' => 'DE', 'rate' => 1900],
            ['country_code' => 'US', 'province_code' => 'CA', 'rate' => 725],
        ])
        ->and(app(TaxCalculator::class)->rateForAddress($settings->taxRatesForCalculator(), 'US', 'CA'))->toBe(725)
        ->and(app(TaxCalculator::class)->rateForAddress($settings->taxRatesForCalculator(), 'DE'))->toBe(1900);
});

it('encrypts provider credentials and preserves them when the key field is left blank', function () {
    $store = shopStore();
    $user = taxSettingsOwner($store);
    $plainTextKey = 'sk_test_tax_secret_value';

    Livewire::actingAs($user)->test(Taxes::class)
        ->set('mode', 'provider')
        ->set('provider', 'stripe_tax')
        ->set('providerApiKey', $plainTextKey)
        ->set('fallback', 'block')
        ->call('save')
        ->assertHasNoErrors();

    $settings = TaxSetting::query()->where('store_id', $store->id)->firstOrFail();
    $cipherText = $settings->config_json['provider_api_key_encrypted'];
    expect($settings->mode)->toBe('provider')
        ->and($settings->provider)->toBe('stripe_tax')
        ->and($cipherText)->not->toBe($plainTextKey)
        ->and(Crypt::decryptString($cipherText))->toBe($plainTextKey)
        ->and($settings->config_json['fallback'])->toBe('block');

    Livewire::actingAs($user)->test(Taxes::class)
        ->assertSet('hasSavedProviderKey', true)
        ->assertSet('providerApiKey', '')
        ->set('mode', 'manual')
        ->set('manualRates', [['zone_name' => 'DE', 'rate_percentage' => '19.00']])
        ->call('save')
        ->assertHasNoErrors();

    expect(TaxSetting::query()->where('store_id', $store->id)->firstOrFail()->config_json['provider_api_key_encrypted'])->toBe($cipherText);
});

it('rejects duplicate or malformed tax zones and requires credentials for a new provider setup', function () {
    $store = shopStore();
    $user = taxSettingsOwner($store);
    $component = Livewire::actingAs($user)->test(Taxes::class)
        ->set('manualRates', [
            ['zone_name' => 'DE', 'rate_percentage' => '19.00'],
            ['zone_name' => 'de', 'rate_percentage' => '20.00'],
        ])
        ->call('save');

    expect(TaxSetting::query()->where('store_id', $store->id)->exists())->toBeFalse();
    $component->assertHasErrors('manualRates.1.zone_name');

    Livewire::actingAs($user)->test(Taxes::class)
        ->set('mode', 'provider')
        ->set('provider', 'stripe_tax')
        ->call('save')
        ->assertHasErrors('providerApiKey');
});
