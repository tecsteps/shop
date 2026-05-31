<?php

namespace App\Services;

use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Support\Collection;

/**
 * Resolves shipping zones for an address and computes rate costs.
 *
 * Zone matching considers the address country (`countries_json`) and region
 * (`regions_json`). {@see self::getMatchingZone()} returns the single most
 * specific zone (used for tax-region resolution and tie-breaking on lowest id);
 * {@see self::getAvailableRates()} returns the active rates of every matching
 * zone so the customer can choose among them.
 *
 * All amounts are integers in minor units (cents).
 */
class ShippingCalculator
{
    /**
     * Active shipping rates available for an address, across all matching zones.
     *
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingRate>
     */
    public function getAvailableRates(Store $store, array $address): Collection
    {
        return $this->matchingZones($store, $address)
            ->flatMap(fn (ShippingZone $zone): Collection => $zone->rates()->where('is_active', true)->get())
            ->values();
    }

    /**
     * The single most specific zone matching an address (region match beats
     * country-only match; ties broken by lowest id), or null when none match.
     *
     * @param  array<string, mixed>  $address
     */
    public function getMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $best = null;
        $bestSpecificity = -1;

        foreach ($this->zonesFor($store) as $zone) {
            $specificity = $this->specificity($zone, $address);

            if ($specificity < 0) {
                continue;
            }

            if ($specificity > $bestSpecificity
                || ($specificity === $bestSpecificity && $best !== null && $zone->id < $best->id)) {
                $best = $zone;
                $bestSpecificity = $specificity;
            }
        }

        return $best;
    }

    /**
     * The cost of a rate for a cart, or null when the rate does not apply to the
     * cart (for example a weight/price range with no matching band).
     *
     * Returns 0 when nothing in the cart requires shipping.
     */
    public function calculate(ShippingRate $rate, Cart $cart): ?int
    {
        if (! $this->cartRequiresShipping($cart)) {
            return 0;
        }

        return match ($rate->type) {
            ShippingRateType::Flat => (int) ($rate->config_json['amount'] ?? 0),
            ShippingRateType::Weight => $this->weightRate($rate, $cart),
            ShippingRateType::Price => $this->priceRate($rate, $cart),
            ShippingRateType::Carrier => $this->carrierRate($rate, $cart),
        };
    }

    /**
     * Whether any cart line's variant requires shipping.
     */
    public function cartRequiresShipping(Cart $cart): bool
    {
        return $cart->lines->contains(
            fn ($line): bool => (bool) ($line->variant?->requires_shipping ?? false),
        );
    }

    /**
     * Zones matching an address (country or region), unordered.
     *
     * @param  array<string, mixed>  $address
     * @return Collection<int, ShippingZone>
     */
    private function matchingZones(Store $store, array $address): Collection
    {
        return $this->zonesFor($store)
            ->filter(fn (ShippingZone $zone): bool => $this->specificity($zone, $address) >= 0)
            ->values();
    }

    /**
     * @return Collection<int, ShippingZone>
     */
    private function zonesFor(Store $store): Collection
    {
        return ShippingZone::query()
            ->where('store_id', $store->id)
            ->with('rates')
            ->get();
    }

    /**
     * Match specificity: 2 = country+region, 1 = country only, -1 = no match.
     *
     * @param  array<string, mixed>  $address
     */
    private function specificity(ShippingZone $zone, array $address): int
    {
        $country = $address['country'] ?? $address['country_code'] ?? null;
        $region = $address['province_code'] ?? $address['region_code'] ?? null;

        $countryMatch = $country !== null && in_array($country, $zone->countries_json ?? [], true);
        $regionMatch = $region !== null && in_array($region, $zone->regions_json ?? [], true);

        return match (true) {
            $countryMatch && $regionMatch => 2,
            $countryMatch => 1,
            $regionMatch => 1,
            default => -1,
        };
    }

    private function weightRate(ShippingRate $rate, Cart $cart): ?int
    {
        $totalWeight = 0;

        foreach ($cart->lines as $line) {
            if ($line->variant?->requires_shipping) {
                $totalWeight += (int) ($line->variant->weight_g ?? 0) * $line->quantity;
            }
        }

        foreach ($rate->config_json['ranges'] ?? [] as $range) {
            if ($totalWeight >= ($range['min_g'] ?? 0) && $totalWeight <= ($range['max_g'] ?? PHP_INT_MAX)) {
                return (int) $range['amount'];
            }
        }

        return null;
    }

    private function priceRate(ShippingRate $rate, Cart $cart): ?int
    {
        $subtotal = $cart->subtotalAmount();

        foreach ($rate->config_json['ranges'] ?? [] as $range) {
            $min = $range['min_amount'] ?? 0;
            $max = $range['max_amount'] ?? null;

            if ($subtotal >= $min && ($max === null || $subtotal <= $max)) {
                return (int) $range['amount'];
            }
        }

        return null;
    }

    /**
     * Carrier rates require an external API; until that integration lands a
     * fixed placeholder amount may be configured, otherwise the rate is
     * unavailable.
     */
    private function carrierRate(ShippingRate $rate, Cart $cart): ?int
    {
        return isset($rate->config_json['amount']) ? (int) $rate->config_json['amount'] : null;
    }
}
