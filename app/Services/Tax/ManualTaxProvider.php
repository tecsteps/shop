<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

class ManualTaxProvider implements TaxProvider
{
    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $taxLines = [];
        $total = 0;

        foreach ($request->lineItems as $line) {
            $amount = intdiv((int) ($line['amount'] ?? 0) * (int) ($line['rate'] ?? 0), 10000);
            $total += $amount;
            $taxLines[] = new TaxLine((string) ($line['name'] ?? 'Tax'), (int) ($line['rate'] ?? 0), $amount);
        }

        return new TaxCalculationResult($taxLines, $total);
    }
}
