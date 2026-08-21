<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxLine;
use App\ValueObjects\TaxResult;

class ManualTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxResult
    {
        $country = strtoupper((string) ($request->address['country_code'] ?? ''));
        $rate = (int) ($request->settings->rates_json[$country] ?? $request->settings->default_rate_basis_points);
        $taxLines = [];

        foreach ($request->lineItems as $line) {
            $amount = (int) ($line['amount'] ?? $line['line_total_amount'] ?? 0);
            $tax = $request->settings->prices_include_tax || $request->settings->mode === 'inclusive'
                ? $this->extractInclusive($amount, $rate)
                : $this->roundTax($amount, $rate);

            if ($tax > 0) {
                $taxLines[] = new TaxLine('Sales tax', $rate, $tax);
            }
        }

        if ($request->shippingAmount > 0) {
            $tax = $request->settings->prices_include_tax || $request->settings->mode === 'inclusive'
                ? $this->extractInclusive($request->shippingAmount, $rate)
                : $this->roundTax($request->shippingAmount, $rate);

            if ($tax > 0) {
                $taxLines[] = new TaxLine('Shipping tax', $rate, $tax);
            }
        }

        return new TaxResult(array_sum(array_map(fn (TaxLine $line): int => $line->amount, $taxLines)), $taxLines);
    }

    private function roundTax(int $amount, int $rate): int
    {
        return (int) floor(($amount * $rate / 10000) + 0.5);
    }

    private function extractInclusive(int $amount, int $rate): int
    {
        return $rate > 0 ? $amount - intdiv($amount * 10000, 10000 + $rate) : 0;
    }
}
