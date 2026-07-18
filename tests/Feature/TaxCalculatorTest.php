<?php

use App\Models\TaxSettings;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates exclusive tax using integer basis points', function () {
    $settings = TaxSettings::factory()->create(['config_json' => ['default_rate' => 1900]]);
    $tax = app(TaxCalculator::class)->calculate(1000, $settings, []);

    expect($tax->amount)->toBe(190)
        ->and($tax->rate)->toBe(1900);
});

it('extracts inclusive tax deterministically', function () {
    expect(app(TaxCalculator::class)->extractInclusive(1190, 1900))->toBe(190);
});
