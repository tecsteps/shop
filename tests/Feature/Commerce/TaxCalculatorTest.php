<?php

use App\Enums\TaxMode;
use App\Enums\TaxProviderType;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\TaxCalculator;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeTaxSettings(array $config = [], bool $pricesInclude = false, TaxMode $mode = TaxMode::Manual): TaxSettings
{
    $store = Store::factory()->create();

    return TaxSettings::factory()->create([
        'store_id' => $store->getKey(),
        'mode' => $mode->value,
        'provider' => TaxProviderType::None->value,
        'prices_include_tax' => $pricesInclude ? 1 : 0,
        'config_json' => $config,
    ]);
}

it('adds tax exclusively on net amounts using country rate', function () {
    $settings = makeTaxSettings(['country_rates' => ['US' => 800]]);

    $result = app(TaxCalculator::class)->calculate(
        [['amount' => 1000, 'label' => 'Line']],
        $settings,
        ['country_code' => 'US'],
    );

    expect($result['total'])->toBe(80)
        ->and($result['lines'])->toHaveCount(1);
});

it('extracts tax from gross amounts when prices include tax', function () {
    $settings = makeTaxSettings(['default_rate_bps' => 1900], pricesInclude: true);

    $result = app(TaxCalculator::class)->calculate(
        [['amount' => 1190]],
        $settings,
        ['country_code' => 'DE'],
    );

    expect($result['total'])->toBe(190);
});

it('falls back to default rate when country not in mapping', function () {
    $settings = makeTaxSettings([
        'country_rates' => ['CA' => 500],
        'default_rate_bps' => 700,
    ]);

    $result = app(TaxCalculator::class)->calculate(
        [['amount' => 1000]],
        $settings,
        ['country_code' => 'US'],
    );

    expect($result['total'])->toBe(70);
});

it('returns zero tax when no rate is configured', function () {
    $settings = makeTaxSettings();

    $result = app(TaxCalculator::class)->calculate(
        [['amount' => 1000]],
        $settings,
        ['country_code' => 'US'],
    );

    expect($result['total'])->toBe(0)
        ->and($result['lines'])->toBe([]);
});

it('skips manual calculation when mode is provider', function () {
    $settings = makeTaxSettings(['default_rate_bps' => 1000], mode: TaxMode::Provider);

    $result = app(TaxCalculator::class)->calculate(
        [['amount' => 1000]],
        $settings,
        ['country_code' => 'US'],
    );

    expect($result['total'])->toBe(0);
});

it('taxes shipping when shipping_taxable flag is set', function () {
    $settings = makeTaxSettings([
        'default_rate_bps' => 1000,
        'shipping_taxable' => true,
    ]);

    $result = app(TaxCalculator::class)->calculate(
        [['amount' => 1000]],
        $settings,
        ['country_code' => 'US'],
        shippingAmount: 500,
    );

    expect($result['total'])->toBe(150)
        ->and($result['lines'])->toHaveCount(2);
});
