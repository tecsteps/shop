<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;

class CheckoutService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private InventoryService $inventoryService,
    ) {}

    /**
     * Create a checkout from a cart.
     */
    public function createFromCart(Cart $cart): Checkout
    {
        $cart->loadMissing('lines');

        if ($cart->lines->isEmpty()) {
            throw new \InvalidArgumentException('Cannot create checkout for empty cart.');
        }

        /** @var Checkout $checkout */
        $checkout = Checkout::query()->withoutGlobalScopes()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
        ]);

        $this->recalculatePricing($checkout);

        return $checkout;
    }

    /**
     * Set address on a checkout (started -> addressed).
     *
     * @param  array{email?: string, first_name: string, last_name: string, address1: string, city: string, province_code?: string, country_code: string, zip: string}  $addressData
     */
    public function setAddress(Checkout $checkout, array $addressData): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::Addressed);

        $requiredFields = ['first_name', 'last_name', 'address1', 'city', 'country_code', 'zip'];
        foreach ($requiredFields as $field) {
            if (empty($addressData[$field])) {
                throw new \InvalidArgumentException("Missing required address field: {$field}.");
            }
        }

        $checkout->update([
            'email' => $addressData['email'] ?? $checkout->email,
            'shipping_address_json' => $addressData,
            'status' => CheckoutStatus::Addressed,
        ]);

        $this->recalculatePricing($checkout);

        return $checkout->refresh();
    }

    /**
     * Set shipping method (addressed -> shipping_selected).
     */
    public function setShippingMethod(Checkout $checkout, int $rateId): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::ShippingSelected);

        /** @var ShippingRate $rate */
        $rate = ShippingRate::query()->findOrFail($rateId);

        $shippingAddress = $checkout->shipping_address_json ?? [];
        $countryCode = $shippingAddress['country_code'] ?? '';

        $zone = $rate->zone;
        $countries = $zone->countries_json ?? [];
        $regions = $zone->regions_json ?? [];
        $provinceCode = $shippingAddress['province_code'] ?? '';

        $zoneMatches = in_array($countryCode, $countries, true)
            || in_array($provinceCode, $regions, true);

        if (! $zoneMatches) {
            throw new \InvalidArgumentException('Shipping rate does not apply to the delivery address.');
        }

        $checkout->update([
            'shipping_method_id' => $rateId,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        $this->recalculatePricing($checkout);

        return $checkout->refresh();
    }

    /**
     * Select payment method (shipping_selected -> payment_selected).
     */
    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::PaymentSelected);

        // Reserve inventory
        $checkout->loadMissing('cart.lines.variant.inventoryItem');
        foreach ($checkout->cart->lines as $line) {
            if ($line->variant->inventoryItem) {
                $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
            }
        }

        $checkout->update([
            'payment_method' => $paymentMethod,
            'status' => CheckoutStatus::PaymentSelected,
            'expires_at' => now()->addMinutes(30),
        ]);

        return $checkout->refresh();
    }

    /**
     * Complete checkout (payment_selected -> completed). Idempotent.
     *
     * Inventory commit and discount usage increment are handled exclusively
     * by OrderService::createFromCheckout to prevent double operations.
     */
    public function completeCheckout(Checkout $checkout): Checkout
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            return $checkout;
        }

        $this->assertTransition($checkout, CheckoutStatus::Completed);

        $checkout->update([
            'status' => CheckoutStatus::Completed,
            'completed_at' => now(),
        ]);

        $checkout->cart->update(['status' => CartStatus::Converted]);

        return $checkout->refresh();
    }

    /**
     * Expire a checkout and release reserved inventory.
     */
    public function expireCheckout(Checkout $checkout): Checkout
    {
        if ($checkout->status === CheckoutStatus::PaymentSelected) {
            $checkout->loadMissing('cart.lines.variant.inventoryItem');
            foreach ($checkout->cart->lines as $line) {
                if ($line->variant->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }
        }

        $checkout->update(['status' => CheckoutStatus::Expired]);

        return $checkout->refresh();
    }

    /**
     * Apply a discount code to a checkout.
     */
    public function applyDiscount(Checkout $checkout, string $code): Checkout
    {
        $checkout->update(['discount_code' => $code]);
        $this->recalculatePricing($checkout);

        return $checkout->refresh();
    }

    /**
     * Remove discount from checkout.
     */
    public function removeDiscount(Checkout $checkout): Checkout
    {
        $checkout->update(['discount_code' => null]);
        $this->recalculatePricing($checkout);

        return $checkout->refresh();
    }

    /**
     * Recalculate pricing and store in totals_json.
     */
    public function recalculatePricing(Checkout $checkout): void
    {
        $checkout->refresh();
        $result = $this->pricingEngine->calculate($checkout);
        $checkout->update(['totals_json' => $result->toArray()]);
    }

    /**
     * Validate a checkout state transition.
     *
     * @throws InvalidCheckoutTransitionException
     */
    private function assertTransition(Checkout $checkout, CheckoutStatus $to): void
    {
        $validTransitions = [
            CheckoutStatus::Started->value => [CheckoutStatus::Addressed, CheckoutStatus::Expired],
            CheckoutStatus::Addressed->value => [CheckoutStatus::ShippingSelected, CheckoutStatus::Addressed, CheckoutStatus::Expired],
            CheckoutStatus::ShippingSelected->value => [CheckoutStatus::PaymentSelected, CheckoutStatus::ShippingSelected, CheckoutStatus::Expired],
            CheckoutStatus::PaymentSelected->value => [CheckoutStatus::Completed, CheckoutStatus::Expired],
        ];

        $allowed = $validTransitions[$checkout->status->value] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidCheckoutTransitionException($checkout->status, $to);
        }
    }
}
