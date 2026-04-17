<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricing,
        private readonly DiscountService $discounts,
        private readonly ShippingCalculator $shipping,
        private readonly InventoryService $inventory,
    ) {}

    public function start(Store $store, Cart $cart): Checkout
    {
        $existing = Checkout::query()
            ->where('cart_id', $cart->getKey())
            ->whereIn('status', [
                CheckoutStatus::Started->value,
                CheckoutStatus::Addressed->value,
                CheckoutStatus::ShippingSelected->value,
                CheckoutStatus::PaymentSelected->value,
            ])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $checkout = Checkout::query()->create([
            'store_id' => $store->getKey(),
            'cart_id' => $cart->getKey(),
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started->value,
        ]);

        $this->snapshotTotals($checkout);

        return $checkout;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertActive($checkout);

        $email = (string) ($data['email'] ?? '');
        $shipping = (array) ($data['shipping_address'] ?? []);
        $billing = (array) ($data['billing_address'] ?? $shipping);

        if ($email === '') {
            throw new InvalidCheckoutStateException('Email is required.');
        }

        foreach (['first_name', 'last_name', 'address1', 'city', 'country_code', 'postal_code'] as $field) {
            if (! isset($shipping[$field]) || trim((string) $shipping[$field]) === '') {
                throw new InvalidCheckoutStateException("Address field '{$field}' is required.");
            }
        }

        $checkout->email = $email;
        $checkout->shipping_address_json = $shipping;
        $checkout->billing_address_json = $billing;
        $checkout->status = CheckoutStatus::Addressed;
        $checkout->save();

        $this->snapshotTotals($checkout);

        return $checkout->refresh();
    }

    public function setShippingMethod(Checkout $checkout, int $rateId): Checkout
    {
        $this->assertActive($checkout);

        if ($checkout->status === CheckoutStatus::Started) {
            throw new InvalidCheckoutStateException('Address must be set before shipping.');
        }

        $address = $checkout->shipping_address_json ?? [];
        $rate = ShippingRate::query()->findOrFail($rateId);

        $zone = $rate->zone;
        $matchingZone = $this->shipping->getMatchingZone($checkout->store, $address);

        if ($matchingZone === null || (int) $matchingZone->getKey() !== (int) $zone->getKey()) {
            throw new InvalidCheckoutStateException('Shipping rate does not apply to this address.');
        }

        $checkout->shipping_method_id = $rate->getKey();
        $checkout->status = CheckoutStatus::ShippingSelected;
        $checkout->save();

        $this->snapshotTotals($checkout);

        return $checkout->refresh();
    }

    public function applyDiscount(Checkout $checkout, string $code): Checkout
    {
        $discount = $this->discounts->validate($code, $checkout->store, $checkout->cart);

        $checkout->discount_code = $discount->code;
        $checkout->save();

        $this->snapshotTotals($checkout);

        return $checkout->refresh();
    }

    public function removeDiscount(Checkout $checkout): Checkout
    {
        $checkout->discount_code = null;
        $checkout->save();

        $this->snapshotTotals($checkout);

        return $checkout->refresh();
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod $method): Checkout
    {
        $this->assertActive($checkout);

        if (! in_array($checkout->status, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected], true)) {
            throw new InvalidCheckoutStateException('Cannot select payment for this checkout state.');
        }

        return DB::transaction(function () use ($checkout, $method): Checkout {
            if ($checkout->status !== CheckoutStatus::PaymentSelected) {
                foreach ($checkout->cart->lines()->with('variant')->get() as $line) {
                    if ($line->variant && $line->variant->requires_shipping) {
                        $this->inventory->reserve($line->variant, (int) $line->quantity);
                    }
                }
            }

            $checkout->payment_method = $method;
            $checkout->status = CheckoutStatus::PaymentSelected;
            $checkout->expires_at = now()->addHours(24);
            $checkout->save();

            $this->snapshotTotals($checkout);

            return $checkout->refresh();
        });
    }

    public function markCompleted(Checkout $checkout): Checkout
    {
        $checkout->status = CheckoutStatus::Completed;
        $checkout->save();

        return $checkout->refresh();
    }

    public function expireCheckout(Checkout $checkout): Checkout
    {
        if (! $checkout->status->isActive()) {
            return $checkout;
        }

        return DB::transaction(function () use ($checkout): Checkout {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                foreach ($checkout->cart->lines()->with('variant')->get() as $line) {
                    if ($line->variant && $line->variant->requires_shipping) {
                        $this->inventory->release($line->variant, (int) $line->quantity);
                    }
                }
            }

            $checkout->status = CheckoutStatus::Expired;
            $checkout->save();

            return $checkout;
        });
    }

    protected function snapshotTotals(Checkout $checkout): void
    {
        try {
            $result = $this->pricing->calculate($checkout);
            $checkout->totals_json = $result->toArray();
            $checkout->save();
        } catch (InvalidDiscountException) {
            // pricing engine swallows discount exceptions internally; keep any prior snapshot
        }
    }

    protected function assertActive(Checkout $checkout): void
    {
        if (! $checkout->status->isActive()) {
            throw new InvalidCheckoutStateException('Checkout is no longer active.');
        }
    }
}
