<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected PricingEngine $pricingEngine,
        protected ShippingCalculator $shippingCalculator,
        protected InventoryService $inventoryService,
    ) {}

    public function createFromCart(Cart $cart): Checkout
    {
        return Checkout::create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        if ($checkout->status !== CheckoutStatus::Started && $checkout->status !== CheckoutStatus::Addressed) {
            throw new InvalidCheckoutTransitionException(
                "Cannot set address when checkout status is {$checkout->status->value}."
            );
        }

        $checkout->update([
            'email' => $data['email'],
            'shipping_address_json' => $data['shipping_address'],
            'billing_address_json' => $data['billing_address'] ?? $data['shipping_address'],
            'status' => CheckoutStatus::Addressed,
        ]);

        $this->pricingEngine->calculate($checkout->fresh());

        return $checkout->fresh();
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        if ($checkout->status !== CheckoutStatus::Addressed && $checkout->status !== CheckoutStatus::ShippingSelected) {
            throw new InvalidCheckoutTransitionException(
                "Cannot set shipping when checkout status is {$checkout->status->value}."
            );
        }

        $cart = $checkout->cart()->with('lines.variant')->first();
        $requiresShipping = $cart->lines->some(fn ($line) => $line->variant->requires_shipping);

        if (! $requiresShipping) {
            $checkout->update([
                'shipping_method_id' => null,
                'status' => CheckoutStatus::ShippingSelected,
            ]);
        } else {
            if (! $shippingRateId) {
                throw new \InvalidArgumentException('Shipping rate is required for physical items.');
            }

            $rate = ShippingRate::findOrFail($shippingRateId);
            $zone = $rate->zone;
            $address = $checkout->shipping_address_json ?? [];
            $matchingZone = $this->shippingCalculator->getMatchingZone($checkout->store, $address);

            if (! $matchingZone || $matchingZone->id !== $zone->id) {
                throw new \InvalidArgumentException('Shipping rate does not apply to the given address.');
            }

            $checkout->update([
                'shipping_method_id' => $shippingRateId,
                'status' => CheckoutStatus::ShippingSelected,
            ]);
        }

        $this->pricingEngine->calculate($checkout->fresh());

        return $checkout->fresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        if ($checkout->status !== CheckoutStatus::ShippingSelected) {
            throw new InvalidCheckoutTransitionException(
                "Cannot select payment when checkout status is {$checkout->status->value}."
            );
        }

        $validMethods = ['credit_card', 'paypal', 'bank_transfer'];
        if (! in_array($paymentMethod, $validMethods)) {
            throw new \InvalidArgumentException("Invalid payment method: {$paymentMethod}.");
        }

        return DB::transaction(function () use ($checkout, $paymentMethod) {
            $checkout->update([
                'payment_method' => $paymentMethod,
                'status' => CheckoutStatus::PaymentPending,
                'expires_at' => now()->addHours(24),
            ]);

            // Reserve inventory
            $cart = $checkout->cart()->with([
                'lines.variant.inventoryItem' => fn ($q) => $q->withoutGlobalScopes(),
            ])->first();
            foreach ($cart->lines as $line) {
                if ($line->variant->inventoryItem) {
                    $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
                }
            }

            return $checkout->fresh();
        });
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::Completed || $checkout->status === CheckoutStatus::Expired) {
            return;
        }

        DB::transaction(function () use ($checkout) {
            if ($checkout->status === CheckoutStatus::PaymentPending) {
                $cart = $checkout->cart()->with([
                    'lines.variant.inventoryItem' => fn ($q) => $q->withoutGlobalScopes(),
                ])->first();
                foreach ($cart->lines as $line) {
                    if ($line->variant->inventoryItem) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);
        });
    }

    public function applyDiscountCode(Checkout $checkout, string $code): Checkout
    {
        $checkout->update(['discount_code' => $code]);
        $this->pricingEngine->calculate($checkout->fresh());

        return $checkout->fresh();
    }
}
