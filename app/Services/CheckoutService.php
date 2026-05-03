<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\UnavailableShippingRateException;
use App\Models\Cart;
use App\Models\Checkout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ShippingCalculator $shipping,
        private readonly PricingEngine $pricing,
        private readonly DiscountService $discounts,
    ) {}

    public function createFromCart(Cart $cart, string $email): Checkout
    {
        return DB::transaction(function () use ($cart, $email): Checkout {
            $cart = Cart::withoutGlobalScopes()
                ->with('lines')
                ->whereKey($cart->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cart->status !== CartStatus::Active) {
                throw new CheckoutStateException('Only active carts may be checked out.');
            }

            if ($cart->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart_id' => ['The cart must contain at least one line.'],
                ]);
            }

            $checkout = Checkout::query()->create([
                'store_id' => $cart->store_id,
                'cart_id' => $cart->id,
                'customer_id' => $cart->customer_id,
                'status' => CheckoutStatus::Started,
                'email' => $email,
                'discount_code' => $cart->discount_code,
                'expires_at' => now()->addDay(),
            ]);

            try {
                $this->pricing->calculate($checkout);
            } catch (InvalidDiscountException) {
                $checkout->forceFill(['discount_code' => null])->save();
                $this->pricing->calculate($checkout);
            }

            return $checkout->refresh()->load('cart.lines.variant.product', 'shippingRate');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        return DB::transaction(function () use ($checkout, $data): Checkout {
            $checkout = $this->lockCheckout($checkout);
            $this->guardActiveCheckout($checkout);

            $shippingAddress = $data['shipping_address'] ?? [];
            $billingAddress = ($data['use_shipping_as_billing'] ?? true)
                ? $shippingAddress
                : ($data['billing_address'] ?? $shippingAddress);

            $checkout->forceFill([
                'shipping_address_json' => $shippingAddress,
                'billing_address_json' => $billingAddress,
                'status' => $this->shipping->requiresShipping($checkout->cart)
                    ? CheckoutStatus::Addressed
                    : CheckoutStatus::ShippingSelected,
            ])->save();

            $this->pricing->calculate($checkout);

            return $checkout->refresh()->load('cart.lines.variant.product', 'shippingRate');
        });
    }

    public function setShippingMethod(Checkout $checkout, int $shippingRateId): Checkout
    {
        return DB::transaction(function () use ($checkout, $shippingRateId): Checkout {
            $checkout = $this->lockCheckout($checkout);
            $this->guardActiveCheckout($checkout);

            if (! in_array($checkout->status, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected], true)) {
                throw new CheckoutStateException('The checkout address must be set before selecting shipping.');
            }

            if (! $this->shipping->requiresShipping($checkout->cart)) {
                $checkout->forceFill([
                    'shipping_method_id' => null,
                    'status' => CheckoutStatus::ShippingSelected,
                ])->save();
                $this->pricing->calculate($checkout);

                return $checkout->refresh()->load('cart.lines.variant.product', 'shippingRate');
            }

            $quotes = $this->shipping->getAvailableRateQuotes(
                $checkout->store,
                $checkout->cart,
                $checkout->shipping_address_json ?? [],
            );

            if (! $quotes->contains(fn ($quote): bool => $quote->rate->id === $shippingRateId)) {
                throw new UnavailableShippingRateException('The selected shipping rate is not available for this checkout.');
            }

            $checkout->forceFill([
                'shipping_method_id' => $shippingRateId,
                'status' => CheckoutStatus::ShippingSelected,
            ])->save();
            $this->pricing->calculate($checkout);

            return $checkout->refresh()->load('cart.lines.variant.product', 'shippingRate');
        });
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod $method): Checkout
    {
        return DB::transaction(function () use ($checkout, $method): Checkout {
            $checkout = $this->lockCheckout($checkout);
            $this->guardActiveCheckout($checkout);

            if (! in_array($checkout->status, [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected], true)) {
                throw new CheckoutStateException('Shipping must be selected before payment.');
            }

            if ($checkout->status !== CheckoutStatus::PaymentSelected) {
                $this->reserveInventory($checkout);
            }

            $checkout->forceFill([
                'payment_method' => $method,
                'status' => CheckoutStatus::PaymentSelected,
                'expires_at' => now()->addDay(),
            ])->save();
            $this->pricing->calculate($checkout);

            return $checkout->refresh()->load('cart.lines.variant.product', 'shippingRate');
        });
    }

    public function applyDiscount(Checkout $checkout, string $code): Checkout
    {
        return DB::transaction(function () use ($checkout, $code): Checkout {
            $checkout = $this->lockCheckout($checkout);
            $this->guardActiveCheckout($checkout);
            $discount = $this->discounts->validate($code, $checkout->store, $checkout->cart);

            $checkout->forceFill([
                'discount_code' => Str::upper((string) $discount->code),
            ])->save();
            $this->pricing->calculate($checkout);

            return $checkout->refresh()->load('cart.lines.variant.product', 'shippingRate');
        });
    }

    public function removeDiscount(Checkout $checkout): Checkout
    {
        return DB::transaction(function () use ($checkout): Checkout {
            $checkout = $this->lockCheckout($checkout);
            $this->guardActiveCheckout($checkout);

            if ($checkout->discount_code === null) {
                throw new CheckoutStateException('No discount is applied to this checkout.');
            }

            $checkout->forceFill(['discount_code' => null])->save();
            $this->pricing->calculate($checkout);

            return $checkout->refresh()->load('cart.lines.variant.product', 'shippingRate');
        });
    }

    public function expireCheckout(Checkout $checkout): void
    {
        DB::transaction(function () use ($checkout): void {
            $checkout = $this->lockCheckout($checkout);

            if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
                return;
            }

            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $this->releaseInventory($checkout);
            }

            $checkout->forceFill(['status' => CheckoutStatus::Expired])->save();
        });
    }

    public function findForStore(int $checkoutId): Checkout
    {
        return Checkout::query()
            ->with('cart.lines.variant.product', 'shippingRate')
            ->whereKey($checkoutId)
            ->firstOrFail();
    }

    private function lockCheckout(Checkout $checkout): Checkout
    {
        return Checkout::withoutGlobalScopes()
            ->with('store', 'cart.lines.variant.inventoryItem', 'cart.lines.variant.product.collections', 'shippingRate')
            ->whereKey($checkout->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function guardActiveCheckout(Checkout $checkout): void
    {
        if ($checkout->isExpired()) {
            throw new CheckoutStateException('This checkout has expired.');
        }

        if ($checkout->status === CheckoutStatus::Completed) {
            throw new CheckoutStateException('This checkout is already completed.');
        }
    }

    private function reserveInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->reserve($item, $line->quantity);
            }
        }
    }

    private function releaseInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }
}
