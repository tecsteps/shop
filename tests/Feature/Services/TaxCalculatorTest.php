<?php

use App\Services\TaxCalculator;

beforeEach(function () {
    $this->calculator = new TaxCalculator;
});

describe('extractInclusive', function () {
    it('extracts 19% tax from gross amount', function () {
        // 1190 gross, 19% rate = 1900 bps
        // net = intdiv(1190 * 10000, 11900) = intdiv(11900000, 11900) = 1000
        // tax = 1190 - 1000 = 190
        $tax = $this->calculator->extractInclusive(1190, 1900);

        expect($tax)->toBe(190);
    });

    it('extracts 8% tax from gross amount', function () {
        // 1080 gross, 8% rate = 800 bps
        // net = intdiv(1080 * 10000, 10800) = intdiv(10800000, 10800) = 1000
        // tax = 1080 - 1000 = 80
        $tax = $this->calculator->extractInclusive(1080, 800);

        expect($tax)->toBe(80);
    });

    it('returns zero for zero rate', function () {
        $tax = $this->calculator->extractInclusive(1000, 0);

        expect($tax)->toBe(0);
    });

    it('returns zero for zero amount', function () {
        $tax = $this->calculator->extractInclusive(0, 1900);

        expect($tax)->toBe(0);
    });

    it('uses integer division for deterministic results', function () {
        // 999 gross, 19% rate = 1900 bps
        // net = intdiv(999 * 10000, 11900) = intdiv(9990000, 11900) = 839
        // tax = 999 - 839 = 160
        $tax = $this->calculator->extractInclusive(999, 1900);

        expect($tax)->toBe(160);
    });
});

describe('addExclusive', function () {
    it('adds 19% tax to net amount', function () {
        // 1000 net, 19% rate = 1900 bps
        // tax = round(1000 * 1900 / 10000) = round(190) = 190
        $tax = $this->calculator->addExclusive(1000, 1900);

        expect($tax)->toBe(190);
    });

    it('adds 8% tax to net amount', function () {
        // 1000 net, 8% rate = 800 bps
        // tax = round(1000 * 800 / 10000) = round(80) = 80
        $tax = $this->calculator->addExclusive(1000, 800);

        expect($tax)->toBe(80);
    });

    it('rounds correctly for fractional results', function () {
        // 333 net, 7% rate = 700 bps
        // tax = round(333 * 700 / 10000) = round(23.31) = 23
        $tax = $this->calculator->addExclusive(333, 700);

        expect($tax)->toBe(23);
    });

    it('returns zero for zero rate', function () {
        $tax = $this->calculator->addExclusive(1000, 0);

        expect($tax)->toBe(0);
    });

    it('returns zero for zero amount', function () {
        $tax = $this->calculator->addExclusive(0, 1900);

        expect($tax)->toBe(0);
    });
});

describe('calculate', function () {
    it('calculates tax-exclusive correctly', function () {
        $settings = new class
        {
            public bool $prices_include_tax = false;

            public array $config_json = [
                'default_rate' => 1900,
                'default_name' => 'VAT',
            ];
        };

        $result = $this->calculator->calculate(1000, $settings, ['country' => 'DE']);

        expect($result['tax_total'])->toBe(190);
        expect($result['tax_lines'])->toHaveCount(1);
        expect($result['tax_lines'][0]->name)->toBe('VAT');
        expect($result['tax_lines'][0]->rate)->toBe(1900);
        expect($result['tax_lines'][0]->amount)->toBe(190);
    });

    it('calculates tax-inclusive correctly', function () {
        $settings = new class
        {
            public bool $prices_include_tax = true;

            public array $config_json = [
                'default_rate' => 1900,
                'default_name' => 'VAT',
            ];
        };

        $result = $this->calculator->calculate(1190, $settings, ['country' => 'DE']);

        expect($result['tax_total'])->toBe(190);
        expect($result['tax_lines'][0]->amount)->toBe(190);
    });

    it('uses country-specific rate when available', function () {
        $settings = new class
        {
            public bool $prices_include_tax = false;

            public array $config_json = [
                'default_rate' => 1900,
                'rates' => [
                    ['countries' => ['US'], 'name' => 'Sales Tax', 'rate' => 800],
                    ['countries' => ['DE'], 'name' => 'MwSt', 'rate' => 1900],
                ],
            ];
        };

        $result = $this->calculator->calculate(1000, $settings, ['country' => 'US']);

        expect($result['tax_total'])->toBe(80);
        expect($result['tax_lines'][0]->name)->toBe('Sales Tax');
        expect($result['tax_lines'][0]->rate)->toBe(800);
    });

    it('falls back to default rate for unknown country', function () {
        $settings = new class
        {
            public bool $prices_include_tax = false;

            public array $config_json = [
                'default_rate' => 500,
                'default_name' => 'Default Tax',
                'rates' => [
                    ['countries' => ['US'], 'name' => 'Sales Tax', 'rate' => 800],
                ],
            ];
        };

        $result = $this->calculator->calculate(1000, $settings, ['country' => 'JP']);

        expect($result['tax_total'])->toBe(50);
        expect($result['tax_lines'][0]->name)->toBe('Default Tax');
    });

    it('returns zero when no rate is configured', function () {
        $settings = new class
        {
            public bool $prices_include_tax = false;

            public array $config_json = [];
        };

        $result = $this->calculator->calculate(1000, $settings, ['country' => 'US']);

        expect($result['tax_total'])->toBe(0);
        expect($result['tax_lines'])->toBeEmpty();
    });

    it('resolves region-specific rate', function () {
        $settings = new class
        {
            public bool $prices_include_tax = false;

            public array $config_json = [
                'default_rate' => 0,
                'rates' => [
                    ['countries' => ['US'], 'regions' => ['US-CA'], 'name' => 'CA Sales Tax', 'rate' => 725],
                    ['countries' => ['US'], 'name' => 'US Sales Tax', 'rate' => 500],
                ],
            ];
        };

        $result = $this->calculator->calculate(1000, $settings, [
            'country' => 'US',
            'province_code' => 'US-CA',
        ]);

        expect($result['tax_total'])->toBe(73); // round(1000 * 725 / 10000) = 73
        expect($result['tax_lines'][0]->name)->toBe('CA Sales Tax');
    });
});
