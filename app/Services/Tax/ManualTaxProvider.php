<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

/**
 * Default tax provider using a manually configured single rate.
 *
 * The rate (basis points) and label come from the store's
 * `tax_settings.config_json`. Tax is rounded per line then summed, and the
 * taxable shipping amount is taxed as an additional line, to prevent rounding
 * drift across lines.
 */
class ManualTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $settings = $request->taxSettings;
        $rate = $settings->defaultRateBasisPoints();

        if ($rate <= 0) {
            return new TaxCalculationResult([], 0);
        }

        $inclusive = $settings->prices_include_tax;
        $name = $settings->taxName();

        $total = 0;

        foreach ([...$request->lineAmounts, $request->shippingAmount] as $amount) {
            $total += $inclusive
                ? $this->extract($amount, $rate)
                : $this->add($amount, $rate);
        }

        if ($total <= 0) {
            return new TaxCalculationResult([], 0);
        }

        return new TaxCalculationResult([new TaxLine($name, $rate, $total)], $total);
    }

    private function add(int $netAmount, int $rate): int
    {
        if ($netAmount <= 0) {
            return 0;
        }

        return intdiv($netAmount * $rate, 10000);
    }

    private function extract(int $grossAmount, int $rate): int
    {
        if ($grossAmount <= 0) {
            return 0;
        }

        return $grossAmount - intdiv($grossAmount * 10000, 10000 + $rate);
    }
}
