<?php

namespace App\Services\Tax;

use App\Contracts\TaxProvider;
use App\Models\Store;
use App\Services\ShippingCalculator;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use App\ValueObjects\TaxLine;

/**
 * Default tax provider: applies the manually configured rate from
 * tax_settings.config_json (spec 05 §8.2).
 *
 * Rate resolution:
 *  1. When config_json.zone_rates is set and the shipping address matches a
 *     shipping zone (same algorithm as shipping), use the rate for that zone.
 *  2. Otherwise fall back to config_json.default_rate_bps.
 *
 * All math is integer-only. Rates are basis points (1900 = 19.00%).
 */
class ManualTaxProvider implements TaxProvider
{
    public function __construct(private ShippingCalculator $shippingCalculator) {}

    public function calculate(TaxCalculationRequest $request): TaxCalculationResult
    {
        $settings = $request->taxSettings;
        $rateBps = $this->resolveRate($request);

        if ($rateBps <= 0) {
            return TaxCalculationResult::zero();
        }

        $name = (string) ($settings->config_json['tax_name'] ?? 'Tax');
        $jurisdiction = $request->address?->countryCode;
        $inclusive = $settings->prices_include_tax;

        $lineDetails = [];
        $total = 0;

        foreach ($request->lineItems as $item) {
            $tax = $inclusive
                ? $this->extractInclusive($item['amount'], $rateBps)
                : $this->addExclusive($item['amount'], $rateBps);

            $lineDetails[] = [
                'variant_id' => $item['variant_id'] ?? null,
                'tax_amount' => $tax,
                'rate' => $rateBps,
                'jurisdiction' => $jurisdiction,
            ];
            $total += $tax;
        }

        $shippingTax = $inclusive
            ? $this->extractInclusive($request->shippingAmount, $rateBps)
            : $this->addExclusive($request->shippingAmount, $rateBps);
        $total += $shippingTax;

        return new TaxCalculationResult(
            taxLines: [new TaxLine(name: $name, rate: $rateBps, amount: $total)],
            totalAmount: $total,
            lineDetails: $lineDetails,
            shippingTaxAmount: $shippingTax,
            shippingTaxRate: $rateBps,
        );
    }

    /**
     * Tax added on top of a net amount. Integer truncation per line, summed
     * afterwards (spec 09 test tables: 8999 @ 700 bps = 629).
     */
    public function addExclusive(int $netAmount, int $rateBasisPoints): int
    {
        return intdiv($netAmount * $rateBasisPoints, 10000);
    }

    /**
     * Tax portion contained in a gross (tax-inclusive) amount.
     * net = intdiv(gross * 10000, 10000 + rate); tax = gross - net.
     */
    public function extractInclusive(int $grossAmount, int $rateBasisPoints): int
    {
        $net = intdiv($grossAmount * 10000, 10000 + $rateBasisPoints);

        return $grossAmount - $net;
    }

    /**
     * Resolve the applicable rate in basis points for the request.
     */
    private function resolveRate(TaxCalculationRequest $request): int
    {
        $config = $request->taxSettings->config_json ?? [];
        $zoneRates = $config['zone_rates'] ?? null;

        if (is_array($zoneRates) && $zoneRates !== [] && $request->address !== null) {
            $store = Store::find($request->taxSettings->store_id);

            if ($store !== null) {
                $zone = $this->shippingCalculator->getMatchingZone($store, $request->address);

                if ($zone !== null && isset($zoneRates[$zone->id])) {
                    return (int) $zoneRates[$zone->id];
                }
            }
        }

        return (int) ($config['default_rate_bps'] ?? 0);
    }
}
