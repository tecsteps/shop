<?php

namespace App\Services\Checkout;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use Illuminate\Support\Str;

class CheckoutService
{
    public function startFromCart(Cart $cart): Checkout
    {
        return Checkout::create([
            'token' => Str::random(48),
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started->value,
            'expires_at' => now()->addHour(),
        ]);
    }

    public function setContact(Checkout $checkout, string $email): void
    {
        $checkout->update(['email' => $email]);
    }

    public function setAddresses(Checkout $checkout, array $shipping, ?array $billing = null): void
    {
        $checkout->update([
            'shipping_address_json' => $shipping,
            'billing_address_json' => $billing ?? $shipping,
            'status' => CheckoutStatus::Addressed->value,
        ]);
    }

    public function selectShipping(Checkout $checkout, int $shippingMethodId): void
    {
        $checkout->update([
            'shipping_method_id' => $shippingMethodId,
            'status' => CheckoutStatus::ShippingSelected->value,
        ]);
    }

    public function applyDiscountCode(Checkout $checkout, ?string $code): void
    {
        $checkout->update(['discount_code' => $code]);
    }

    public function selectPayment(Checkout $checkout, string $method): void
    {
        $checkout->update([
            'payment_method' => $method,
            'status' => CheckoutStatus::PaymentPending->value,
        ]);
    }
}
