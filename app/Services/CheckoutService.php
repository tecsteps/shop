<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCartException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private InventoryService $inventoryService,
        private ShippingCalculator $shippingCalculator,
        private DiscountService $discountService,
    ) {}

    public function createFromCart(Store $store, Cart $cart): Checkout
    {
        if ($cart->lines()->count() === 0) {
            throw new InvalidCartException('Cannot create checkout from empty cart.');
        }

        $checkout = Checkout::create([
            'store_id' => $store->id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
        ]);

        $this->recalculateTotals($checkout);

        return $checkout->fresh();
    }

    /**
     * @param  array{email: string, shipping_address: array<string, string>, billing_address?: array<string, string>}  $addressData
     */
    public function setAddress(Checkout $checkout, array $addressData): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::Addressed);

        $shippingAddress = $addressData['shipping_address'];
        $billingAddress = $addressData['billing_address'] ?? $shippingAddress;

        $checkout->update([
            'email' => $addressData['email'],
            'shipping_address_json' => $shippingAddress,
            'billing_address_json' => $billingAddress,
            'status' => CheckoutStatus::Addressed,
        ]);

        $this->recalculateTotals($checkout);

        return $checkout->fresh();
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::ShippingSelected);

        $cart = $checkout->cart()->with('lines.variant')->first();
        $requiresShipping = $cart->lines->contains(fn ($line) => $line->variant->requires_shipping);

        if (! $requiresShipping) {
            $checkout->update([
                'shipping_method_id' => null,
                'status' => CheckoutStatus::ShippingSelected,
            ]);

            $this->recalculateTotals($checkout);

            return $checkout->fresh();
        }

        if ($shippingRateId) {
            $address = $checkout->shipping_address_json ?? [];
            $availableRates = $this->shippingCalculator->getAvailableRates($checkout->store, $address);
            $validRate = $availableRates->firstWhere('id', $shippingRateId);

            if (! $validRate) {
                throw new InvalidCheckoutTransitionException(
                    $checkout->status->value,
                    'shipping_selected',
                    'Selected shipping rate is not available for this address.',
                );
            }
        }

        $checkout->update([
            'shipping_method_id' => $shippingRateId,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        $this->recalculateTotals($checkout);

        return $checkout->fresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::PaymentSelected);

        $method = PaymentMethod::tryFrom($paymentMethod);

        if (! $method) {
            throw new InvalidCheckoutTransitionException(
                $checkout->status->value,
                'payment_selected',
                'Invalid payment method.',
            );
        }

        return DB::transaction(function () use ($checkout, $method) {
            // Reserve inventory for all cart lines
            $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();

            foreach ($cart->lines as $line) {
                if ($line->variant->inventoryItem) {
                    $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
                }
            }

            $checkout->update([
                'payment_method' => $method,
                'status' => CheckoutStatus::PaymentSelected,
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ]);

            return $checkout->fresh();
        });
    }

    public function completeCheckout(Checkout $checkout): Checkout
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            return $checkout;
        }

        $this->assertTransition($checkout, CheckoutStatus::Completed);

        return DB::transaction(function () use ($checkout) {
            // Commit inventory for credit_card/paypal, keep reserved for bank_transfer
            $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();

            if ($checkout->payment_method !== PaymentMethod::BankTransfer) {
                foreach ($cart->lines as $line) {
                    if ($line->variant->inventoryItem) {
                        $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            // Increment discount usage
            if ($checkout->discount_code) {
                $discount = \App\Models\Discount::withoutGlobalScopes()
                    ->where('store_id', $checkout->store_id)
                    ->whereRaw('LOWER(code) = ?', [strtolower($checkout->discount_code)])
                    ->first();

                if ($discount) {
                    $discount->increment('usage_count');
                }
            }

            $cart->update(['status' => CartStatus::Converted]);

            $checkout->update([
                'status' => CheckoutStatus::Completed,
            ]);

            return $checkout->fresh();
        });
    }

    public function expireCheckout(Checkout $checkout): Checkout
    {
        if ($checkout->status === CheckoutStatus::Completed || $checkout->status === CheckoutStatus::Expired) {
            return $checkout;
        }

        return DB::transaction(function () use ($checkout) {
            // Release reserved inventory if it was reserved
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();

                foreach ($cart->lines as $line) {
                    if ($line->variant->inventoryItem) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);

            return $checkout->fresh();
        });
    }

    public function applyDiscount(Checkout $checkout, string $code): Checkout
    {
        $cart = $checkout->cart()->with('lines.variant')->first();
        $this->discountService->validate($code, $checkout->store, $cart);

        $checkout->update(['discount_code' => $code]);
        $this->recalculateTotals($checkout);

        return $checkout->fresh();
    }

    public function removeDiscount(Checkout $checkout): Checkout
    {
        $checkout->update(['discount_code' => null]);
        $this->recalculateTotals($checkout);

        return $checkout->fresh();
    }

    private function recalculateTotals(Checkout $checkout): void
    {
        $checkout->refresh();
        $result = $this->pricingEngine->calculate($checkout);

        $checkout->update([
            'totals_json' => $result->toArray(),
        ]);
    }

    private function assertTransition(Checkout $checkout, CheckoutStatus $to): void
    {
        $validTransitions = [
            CheckoutStatus::Started->value => [CheckoutStatus::Addressed->value],
            CheckoutStatus::Addressed->value => [
                CheckoutStatus::ShippingSelected->value,
                CheckoutStatus::Addressed->value,
            ],
            CheckoutStatus::ShippingSelected->value => [CheckoutStatus::PaymentSelected->value],
            CheckoutStatus::PaymentSelected->value => [CheckoutStatus::Completed->value],
        ];

        $allowed = $validTransitions[$checkout->status->value] ?? [];

        if (! in_array($to->value, $allowed)) {
            throw new InvalidCheckoutTransitionException(
                $checkout->status->value,
                $to->value,
            );
        }
    }
}
