<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutCompleted;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\ShippingUnavailableException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\ValueObjects\PaymentResult;
use BackedEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricing,
        private readonly ShippingCalculator $shipping,
        private readonly InventoryService $inventory,
        private readonly PaymentService $payments,
        private readonly OrderService $orders,
    ) {}

    public function create(Cart $cart, ?string $email = null): Checkout
    {
        return DB::transaction(function () use ($cart, $email): Checkout {
            $cart = Cart::withoutGlobalScopes()->lockForUpdate()->with('lines')->findOrFail($cart->id);
            if ($this->stateValue($cart->status) !== 'active' || $cart->lines->isEmpty()) {
                throw new InvalidCheckoutTransitionException('Only a non-empty active cart can be checked out.');
            }

            $existing = Checkout::withoutGlobalScopes()
                ->where('cart_id', $cart->id)
                ->whereNotIn('status', ['completed', 'expired'])
                ->latest('id')
                ->first();
            if ($existing !== null && $existing->expires_at?->isFuture()) {
                if ($email !== null && $this->state($existing) === 'started') {
                    $existing->update(['email' => mb_strtolower($email), 'expires_at' => now()->addDay()]);
                }
                $this->pricing->calculate($existing);

                return $existing->refresh();
            }
            if ($existing !== null) {
                $this->expireCheckout($existing);
            }

            $checkout = Checkout::withoutGlobalScopes()->create([
                'store_id' => $cart->store_id,
                'cart_id' => $cart->id,
                'customer_id' => $cart->customer_id,
                'status' => 'started',
                'email' => $email === null ? null : mb_strtolower($email),
                'expires_at' => now()->addDay(),
            ]);
            $this->pricing->calculate($checkout);

            return $checkout->refresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertState($checkout, ['started', 'addressed', 'shipping_selected']);
        $checkout->loadMissing('cart.lines.variant');
        $requiresShipping = $this->shipping->requiresShipping($checkout->cart);
        $address = (array) ($data['shipping_address'] ?? $data['address'] ?? $data);
        $email = (string) ($data['email'] ?? $checkout->email ?? '');
        $rules = [
            'email' => ['required', 'email'],
        ];
        if ($requiresShipping) {
            $rules += [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'address1' => ['required', 'string', 'max:500'],
                'city' => ['required', 'string', 'max:255'],
                'country_code' => ['required', 'string', 'size:2'],
                'postal_code' => ['required', 'string', 'max:20'],
            ];
        }
        Validator::make(['email' => $email, ...$address], $rules)->validate();

        if ($requiresShipping) {
            $checkout->loadMissing('store');
            if ($this->shipping->getAvailableRates($checkout->store, $address, $checkout->cart)->isEmpty()) {
                throw new ShippingUnavailableException('No shipping method is available for this address.');
            }
        }

        $billing = (bool) ($data['use_shipping_as_billing'] ?? true)
            ? $address
            : (array) ($data['billing_address'] ?? []);

        $checkout->fill([
            'email' => mb_strtolower($email),
            'shipping_address_json' => $requiresShipping ? $address : null,
            'billing_address_json' => $billing === [] ? null : $billing,
            'shipping_method_id' => null,
            'status' => 'addressed',
        ])->save();
        $this->pricing->calculate($checkout);
        event(new CheckoutAddressed($checkout));

        return $checkout->refresh();
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->assertState($checkout, ['addressed', 'shipping_selected']);
        $checkout->loadMissing(['store', 'cart.lines.variant']);

        if (! $this->shipping->requiresShipping($checkout->cart)) {
            $checkout->update(['shipping_method_id' => null, 'status' => 'shipping_selected']);
        } else {
            $rates = $this->shipping->getAvailableRates($checkout->store, (array) $checkout->shipping_address_json, $checkout->cart);
            if ($shippingRateId === null || ! $rates->contains('id', $shippingRateId)) {
                throw new ShippingUnavailableException('The selected shipping method is not available for this address.');
            }
            $checkout->update(['shipping_method_id' => $shippingRateId, 'status' => 'shipping_selected']);
        }
        $this->pricing->calculate($checkout);
        event(new CheckoutShippingSelected($checkout));

        return $checkout->refresh();
    }

    public function applyDiscount(Checkout $checkout, ?string $code): Checkout
    {
        $this->assertState($checkout, ['addressed', 'shipping_selected']);
        $checkout->discount_code = $code === null || trim($code) === '' ? null : trim($code);
        $checkout->save();
        $this->pricing->calculate($checkout);

        return $checkout->refresh();
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod|string $paymentMethod): Checkout
    {
        $this->assertState($checkout, ['shipping_selected']);
        $method = $paymentMethod instanceof PaymentMethod ? $paymentMethod : PaymentMethod::from($paymentMethod);
        $checkout->load([
            'cart' => fn ($query) => $query->withoutGlobalScopes(),
            'cart.lines.variant.inventoryItem' => fn ($query) => $query->withoutGlobalScopes(),
        ]);

        DB::transaction(function () use ($checkout, $method): void {
            foreach ($checkout->cart->lines as $line) {
                if ($line->variant->inventoryItem !== null) {
                    $this->inventory->reserve($line->variant->inventoryItem, (int) $line->quantity);
                }
            }
            $checkout->update(['payment_method' => $method, 'status' => 'payment_selected', 'expires_at' => now()->addDay()]);
        });

        return $checkout->refresh();
    }

    /** @param array<string, mixed> $paymentDetails */
    public function completeCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        $outcome = DB::transaction(function () use ($checkout, $paymentDetails): Order|\App\ValueObjects\PaymentResult {
            $checkout = Checkout::withoutGlobalScopes()->lockForUpdate()->with('cart')->findOrFail($checkout->id);
            if ($this->state($checkout) === 'completed') {
                $id = (int) data_get($checkout->totals_json, 'order_id');

                return Order::withoutGlobalScopes()->findOrFail($id);
            }
            if ($checkout->expires_at?->isPast()) {
                $this->expireCheckout($checkout);
                throw new InvalidCheckoutTransitionException('This checkout has expired.');
            }
            $this->assertState($checkout, ['payment_selected']);
            $method = $checkout->payment_method instanceof PaymentMethod
                ? $checkout->payment_method
                : PaymentMethod::from((string) $checkout->payment_method);
            $result = $this->payments->charge($checkout, $method, $paymentDetails);

            if (! $result->success) {
                $this->releaseReservations($checkout);
                $checkout->update(['status' => 'shipping_selected', 'payment_method' => null]);

                return $result;
            }

            $order = $this->orders->createFromCheckout($checkout, $result);
            event(new CheckoutCompleted($checkout, $order));

            return $order;
        });

        if ($outcome instanceof PaymentResult) {
            throw new PaymentFailedException($outcome->errorCode ?? 'payment_failed', $outcome->errorMessage);
        }

        return $outcome;
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($this->state($checkout), ['completed', 'expired'], true)) {
            return;
        }
        if ($this->state($checkout) === 'payment_selected') {
            $this->releaseReservations($checkout);
        }
        $checkout->update(['status' => 'expired']);
        event(new CheckoutExpired($checkout));
    }

    private function releaseReservations(Checkout $checkout): void
    {
        $checkout->load([
            'cart' => fn ($query) => $query->withoutGlobalScopes(),
            'cart.lines.variant.inventoryItem' => fn ($query) => $query->withoutGlobalScopes(),
        ]);
        foreach ($checkout->cart->lines as $line) {
            if ($line->variant->inventoryItem !== null) {
                $this->inventory->release($line->variant->inventoryItem, (int) $line->quantity);
            }
        }
    }

    /** @param list<string> $states */
    private function assertState(Checkout $checkout, array $states): void
    {
        if (! in_array($this->state($checkout), $states, true)) {
            throw new InvalidCheckoutTransitionException('This checkout action is not valid in its current state.');
        }
    }

    private function state(Checkout $checkout): string
    {
        return $this->stateValue($checkout->status);
    }

    private function stateValue(mixed $status): string
    {
        return $status instanceof BackedEnum ? (string) $status->value : (string) $status;
    }
}
