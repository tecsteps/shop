<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutService
{
    /**
     * Valid state transitions for the checkout state machine.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'started' => ['addressed', 'expired'],
        'addressed' => ['shipping_selected', 'expired'],
        'shipping_selected' => ['payment_selected', 'expired'],
        'payment_selected' => ['completed', 'expired'],
    ];

    public function __construct(
        private PricingEngine $pricingEngine,
        private ShippingCalculator $shippingCalculator,
    ) {}

    /**
     * Create a new checkout from a cart.
     */
    public function createCheckout(Cart $cart): Checkout
    {
        if ($cart->lines()->count() === 0) {
            throw new InvalidArgumentException('Cannot create checkout for empty cart');
        }

        return Checkout::withoutGlobalScopes()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
        ]);
    }

    /**
     * Set the address on a checkout.
     *
     * @param  array{email: string, shipping_address: array<string, mixed>, billing_address?: array<string, mixed>}  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->validateTransition($checkout, CheckoutStatus::Addressed);

        return DB::transaction(function () use ($checkout, $data) {
            $shippingAddress = $data['shipping_address'];
            $billingAddress = $data['billing_address'] ?? $shippingAddress;

            $checkout->update([
                'email' => $data['email'],
                'shipping_address_json' => $shippingAddress,
                'billing_address_json' => $billingAddress,
                'status' => CheckoutStatus::Addressed,
            ]);

            $this->recalculate($checkout);

            return $checkout->refresh();
        });
    }

    /**
     * Select a shipping method on a checkout.
     */
    public function setShippingMethod(Checkout $checkout, int $shippingRateId): Checkout
    {
        $this->validateTransition($checkout, CheckoutStatus::ShippingSelected);

        return DB::transaction(function () use ($checkout, $shippingRateId) {
            $rate = ShippingRate::findOrFail($shippingRateId);

            $checkout->update([
                'shipping_method_id' => $rate->id,
                'status' => CheckoutStatus::ShippingSelected,
            ]);

            $this->recalculate($checkout);

            return $checkout->refresh();
        });
    }

    /**
     * Skip shipping selection when no items require shipping.
     */
    public function skipShipping(Checkout $checkout): Checkout
    {
        $this->validateTransition($checkout, CheckoutStatus::ShippingSelected);

        return DB::transaction(function () use ($checkout) {
            $checkout->update([
                'shipping_method_id' => null,
                'status' => CheckoutStatus::ShippingSelected,
            ]);

            $this->recalculate($checkout);

            return $checkout->refresh();
        });
    }

    /**
     * Select a payment method on a checkout.
     */
    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $this->validateTransition($checkout, CheckoutStatus::PaymentSelected);

        $validMethods = ['credit_card', 'paypal', 'bank_transfer'];

        if (! in_array($paymentMethod, $validMethods)) {
            throw new InvalidArgumentException("Invalid payment method: {$paymentMethod}");
        }

        return DB::transaction(function () use ($checkout, $paymentMethod) {
            $checkout->update([
                'payment_method' => $paymentMethod,
                'status' => CheckoutStatus::PaymentSelected,
                'expires_at' => now()->addHours(24),
            ]);

            $this->recalculate($checkout);

            return $checkout->refresh();
        });
    }

    /**
     * Apply a discount code to a checkout.
     */
    public function applyDiscountCode(Checkout $checkout, string $code): Checkout
    {
        return DB::transaction(function () use ($checkout, $code) {
            $checkout->update(['discount_code' => $code]);
            $this->recalculate($checkout);

            return $checkout->refresh();
        });
    }

    /**
     * Remove discount from a checkout.
     */
    public function removeDiscount(Checkout $checkout): Checkout
    {
        return DB::transaction(function () use ($checkout) {
            $checkout->update(['discount_code' => null]);
            $this->recalculate($checkout);

            return $checkout->refresh();
        });
    }

    /**
     * Transition the checkout to completed state.
     */
    public function completeCheckout(Checkout $checkout): Checkout
    {
        $this->validateTransition($checkout, CheckoutStatus::Completed);

        return DB::transaction(function () use ($checkout) {
            $cart = Cart::withoutGlobalScopes()->findOrFail($checkout->cart_id);
            $cart->update(['status' => CartStatus::Converted]);

            $checkout->update([
                'status' => CheckoutStatus::Completed,
            ]);

            return $checkout->refresh();
        });
    }

    /**
     * Expire a checkout.
     */
    public function expireCheckout(Checkout $checkout): Checkout
    {
        if ($checkout->status === CheckoutStatus::Completed || $checkout->status === CheckoutStatus::Expired) {
            throw new InvalidCheckoutTransitionException($checkout->status, CheckoutStatus::Expired);
        }

        $checkout->update([
            'status' => CheckoutStatus::Expired,
        ]);

        return $checkout->refresh();
    }

    /**
     * Validate that a state transition is allowed.
     *
     * @throws InvalidCheckoutTransitionException
     */
    private function validateTransition(Checkout $checkout, CheckoutStatus $target): void
    {
        $current = $checkout->status->value;
        $allowed = self::TRANSITIONS[$current] ?? [];

        if (! in_array($target->value, $allowed)) {
            throw new InvalidCheckoutTransitionException($checkout->status, $target);
        }
    }

    /**
     * Recalculate pricing and store snapshot.
     */
    private function recalculate(Checkout $checkout): void
    {
        $result = $this->pricingEngine->calculate($checkout);

        $checkout->update([
            'totals_json' => $result->toArray(),
        ]);
    }
}
