<?php

use App\Models\TaxSettings;
use App\Services\TaxCalculator;

beforeEach(function (): void {
    $this->calc = new TaxCalculator;
});

it('calculates exclusive tax at 19%', function (): void {
    expect($this->calc->addExclusive(10000, 1900))->toBe(1900);
});

it('extracts inclusive tax at 19%', function (): void {
    expect($this->calc->extractInclusive(11900, 1900))->toBe(1900);
});

it('returns 0 when rate is 0 for exclusive', function (): void {
    expect($this->calc->addExclusive(10000, 0))->toBe(0);
});

it('returns 0 when rate is 0 for inclusive', function (): void {
    expect($this->calc->extractInclusive(10000, 0))->toBe(0);
});

it('returns 0 when amount is 0', function (): void {
    expect($this->calc->addExclusive(0, 1900))->toBe(0)
        ->and($this->calc->extractInclusive(0, 1900))->toBe(0);
});

it('handles non-standard rate 7% exclusive', function (): void {
    expect($this->calc->addExclusive(8999, 700))->toBe(630);
});

it('handles small inclusive extraction', function (): void {
    expect($this->calc->extractInclusive(119, 1900))->toBe(19);
});

it('calculates high 25% exclusive rate', function (): void {
    expect($this->calc->addExclusive(10000, 2500))->toBe(2500);
});

it('produces tax_lines via calculate', function (): void {
    $settings = new TaxSettings([
        'store_id' => 1,
        'mode' => 'manual',
        'prices_include_tax' => false,
        'config_json' => ['name' => 'VAT', 'rate_basis_points' => 1900],
    ]);

    $result = $this->calc->calculate(10000, $settings, []);

    expect($result['tax_total'])->toBe(1900)
        ->and($result['tax_lines'])->toHaveCount(1)
        ->and($result['tax_lines'][0]->name)->toBe('VAT')
        ->and($result['tax_lines'][0]->rate)->toBe(1900)
        ->and($result['tax_lines'][0]->amount)->toBe(1900);
});
