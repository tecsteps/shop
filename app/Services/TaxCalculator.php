<?php

namespace App\Services;

use App\Models\Store;
use App\Models\TaxSettings;
use App\ValueObjects\TaxLine;

class TaxCalculator
{
    /**
     * Calculate tax on a set of line items.
     *
     * @param  list<array{amount: int}>  $lineItems  Discounted line amounts
     * @param  array{country?: string, province_code?: string}  $address
     * @return list<TaxLine>
     */
    public function calculate(array $lineItems, int $shippingAmount, array $address, Store $store): array
    {
        $taxSettings = $store->taxSettings ?? TaxSettings::where('store_id', $store->id)->first();

        if (! $taxSettings) {
            return [];
        }

        $rateBps = $taxSettings->tax_rate_basis_points ?? 0;
        $taxName = $taxSettings->tax_name ?? 'Tax';
        $pricesIncludeTax = $taxSettings->prices_include_tax ?? false;
        $shippingTaxable = $taxSettings->shipping_taxable ?? false;

        if ($rateBps <= 0) {
            return [];
        }

        $totalTax = 0;

        foreach ($lineItems as $item) {
            $amount = $item['amount'];

            if ($pricesIncludeTax) {
                $net = intdiv($amount * 10000, 10000 + $rateBps);
                $lineTax = $amount - $net;
            } else {
                $lineTax = (int) round($amount * $rateBps / 10000);
            }

            $totalTax += $lineTax;
        }

        if ($shippingTaxable && $shippingAmount > 0) {
            if ($pricesIncludeTax) {
                $netShipping = intdiv($shippingAmount * 10000, 10000 + $rateBps);
                $shippingTax = $shippingAmount - $netShipping;
            } else {
                $shippingTax = (int) round($shippingAmount * $rateBps / 10000);
            }

            $totalTax += $shippingTax;
        }

        if ($totalTax <= 0) {
            return [];
        }

        return [
            new TaxLine(
                name: $taxName,
                rate: $rateBps,
                amount: $totalTax,
            ),
        ];
    }
}
