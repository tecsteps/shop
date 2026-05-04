<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\UnserviceableShippingAddressException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ShippingCalculator $shipping,
        private readonly PricingEngine $pricing,
        private readonly OrderService $orders,
    ) {}

    public function createFromCart(Cart $cart, ?Customer $customer = null): Checkout
    {
        return DB::transaction(function () use ($cart, $customer): Checkout {
            $cart = Cart::withoutGlobalScopes()
                ->whereKey($cart->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($cart->status !== CartStatus::Active) {
                throw InvalidCheckoutTransitionException::because('Checkout can only start from an active cart.');
            }

            if (! CartLine::withoutGlobalScopes()->where('cart_id', $cart->getKey())->exists()) {
                throw InvalidCheckoutTransitionException::because('Checkout cannot start from an empty cart.');
            }

            $existingCheckout = Checkout::withoutGlobalScopes()
                ->where('cart_id', $cart->getKey())
                ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existingCheckout instanceof Checkout) {
                return $existingCheckout;
            }

            return Checkout::withoutGlobalScopes()->create([
                'store_id' => $cart->store_id,
                'cart_id' => $cart->getKey(),
                'customer_id' => $customer?->getKey() ?? $cart->customer_id,
                'status' => CheckoutStatus::Started,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    public function setAddress(Checkout $checkout, array $addressData): Checkout
    {
        return DB::transaction(function () use ($checkout, $addressData): Checkout {
            $checkout = $this->freshCheckout($checkout);
            $this->assertStatus($checkout, [CheckoutStatus::Started, CheckoutStatus::Addressed]);

            $email = (string) data_get($addressData, 'email');
            $shippingAddress = data_get($addressData, 'shipping_address', $addressData);

            Validator::make([
                'email' => $email,
                'shipping_address' => $shippingAddress,
            ], [
                'email' => ['required', 'email'],
                'shipping_address.first_name' => ['required', 'string'],
                'shipping_address.last_name' => ['required', 'string'],
                'shipping_address.address1' => ['required', 'string'],
                'shipping_address.city' => ['required', 'string'],
                'shipping_address.country' => ['required', 'string', 'size:2'],
                'shipping_address.postal_code' => ['required', 'string'],
            ])->validate();

            $checkout->forceFill([
                'email' => $email,
                'shipping_address_json' => $shippingAddress,
                'billing_address_json' => data_get($addressData, 'billing_address', $shippingAddress),
                'status' => CheckoutStatus::Addressed,
            ])->save();

            $this->pricing->calculate($checkout);

            return $checkout->refresh();
        });
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        return DB::transaction(function () use ($checkout, $shippingRateId): Checkout {
            $checkout = $this->freshCheckout($checkout);
            $this->assertStatus($checkout, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected]);

            if (! $this->shipping->requiresShipping($checkout->cart)) {
                $checkout->forceFill([
                    'shipping_method_id' => null,
                    'status' => CheckoutStatus::ShippingSelected,
                ])->save();

                $this->pricing->calculate($checkout);

                return $checkout->refresh();
            }

            $availableRates = $this->shipping->getAvailableRates($checkout->store, $checkout->shipping_address_json ?? []);

            if ($availableRates->isEmpty()) {
                throw UnserviceableShippingAddressException::forAddress();
            }

            $rate = $availableRates->firstWhere('id', $shippingRateId);

            if (! $rate instanceof ShippingRate) {
                throw InvalidCheckoutTransitionException::because('Selected shipping rate is not available for this address.');
            }

            $checkout->forceFill([
                'shipping_method_id' => $rate->getKey(),
                'status' => CheckoutStatus::ShippingSelected,
            ])->save();

            $this->pricing->calculate($checkout);

            return $checkout->refresh();
        });
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        return DB::transaction(function () use ($checkout, $paymentMethod): Checkout {
            $checkout = $this->freshCheckout($checkout);

            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                return $checkout;
            }

            $this->assertStatus($checkout, [CheckoutStatus::ShippingSelected]);

            if (! in_array($paymentMethod, ['credit_card', 'paypal', 'bank_transfer'], true)) {
                throw InvalidCheckoutTransitionException::because('Payment method is invalid.');
            }

            if ($checkout->totals_json === null) {
                $this->pricing->calculate($checkout);
            }

            CartLine::withoutGlobalScopes()
                ->where('cart_id', $checkout->cart_id)
                ->get()
                ->each(function (CartLine $line): void {
                    $inventory = InventoryItem::withoutGlobalScopes()
                        ->where('variant_id', $line->variant_id)
                        ->firstOrFail();

                    $this->inventory->reserve($inventory, $line->quantity);
                });

            $checkout->forceFill([
                'payment_method' => $paymentMethod,
                'status' => CheckoutStatus::PaymentSelected,
                'expires_at' => now()->addDay(),
            ])->save();

            return $checkout->refresh();
        });
    }

    public function expireCheckout(Checkout $checkout): Checkout
    {
        return DB::transaction(function () use ($checkout): Checkout {
            $checkout = $this->freshCheckout($checkout);

            if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
                return $checkout;
            }

            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                CartLine::withoutGlobalScopes()
                    ->where('cart_id', $checkout->cart_id)
                    ->get()
                    ->each(function (CartLine $line): void {
                        $inventory = InventoryItem::withoutGlobalScopes()
                            ->where('variant_id', $line->variant_id)
                            ->firstOrFail();

                        $this->inventory->release($inventory, $line->quantity);
                    });
            }

            $checkout->forceFill([
                'status' => CheckoutStatus::Expired,
            ])->save();

            return $checkout->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $paymentMethodData
     */
    public function completeCheckout(Checkout $checkout, array $paymentMethodData = []): Order
    {
        return $this->orders->createFromCheckout($checkout, $paymentMethodData);
    }

    private function freshCheckout(Checkout $checkout): Checkout
    {
        return Checkout::withoutGlobalScopes()
            ->with(['cart', 'store'])
            ->whereKey($checkout->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  array<int, CheckoutStatus>  $allowed
     */
    private function assertStatus(Checkout $checkout, array $allowed): void
    {
        if (! in_array($checkout->status, $allowed, true)) {
            throw InvalidCheckoutTransitionException::because("Checkout cannot transition from {$checkout->status->value}.");
        }
    }
}
