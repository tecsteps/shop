<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;

class CheckoutService
{
    public function __construct(
        private PricingEngine $pricingEngine,
    ) {}

    public function createFromCart(Cart $cart): Checkout
    {
        return Checkout::withoutGlobalScopes()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        if (! in_array($checkout->status, [CheckoutStatus::Started, CheckoutStatus::Addressed])) {
            throw new InvalidCheckoutTransitionException;
        }

        $checkout->update([
            'email' => $data['email'] ?? $checkout->email,
            'shipping_address_json' => $data['shipping_address'] ?? $checkout->shipping_address_json,
            'billing_address_json' => $data['billing_address'] ?? $data['shipping_address'] ?? $checkout->billing_address_json,
            'status' => CheckoutStatus::Addressed,
        ]);

        return $checkout->refresh();
    }

    public function setShippingMethod(Checkout $checkout, int $rateId): Checkout
    {
        if ($checkout->status !== CheckoutStatus::Addressed) {
            throw new InvalidCheckoutTransitionException;
        }

        $rate = \App\Models\ShippingRate::findOrFail($rateId);
        $shippingCalculator = app(ShippingCalculator::class);
        $amount = $shippingCalculator->calculate($rate, $checkout->cart);

        $checkout->update([
            'shipping_rate_id' => $rateId,
            'shipping_method_name' => $rate->name,
            'shipping_amount' => $amount,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        return $checkout->refresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $method): Checkout
    {
        if ($checkout->status !== CheckoutStatus::ShippingSelected) {
            throw new InvalidCheckoutTransitionException;
        }

        $pricing = $this->pricingEngine->calculate($checkout);

        $checkout->update([
            'payment_method' => $method,
            'status' => CheckoutStatus::PaymentPending,
            'totals_json' => [
                'subtotal' => $pricing->subtotal,
                'discount' => $pricing->discount,
                'shipping' => $pricing->shipping,
                'tax_total' => $pricing->taxTotal,
                'total' => $pricing->total,
            ],
        ]);

        return $checkout->refresh();
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function completeCheckout(Checkout $checkout, array $paymentDetails = []): Checkout
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            return $checkout;
        }

        if ($checkout->status !== CheckoutStatus::PaymentPending) {
            throw new InvalidCheckoutTransitionException;
        }

        $checkout->update([
            'status' => CheckoutStatus::Completed,
            'completed_at' => now(),
        ]);

        $checkout->cart->update(['status' => CartStatus::Converted]);

        return $checkout->refresh();
    }

    public function expireCheckout(Checkout $checkout): void
    {
        $checkout->update(['status' => CheckoutStatus::Expired]);
    }
}
