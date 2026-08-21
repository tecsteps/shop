<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutService
{
    public function __construct(private readonly PricingEngine $pricing, private readonly ShippingCalculator $shipping, private readonly InventoryService $inventory) {}

    public function create(Cart $cart, string $email, ?Customer $customer = null): Checkout
    {
        $cart->load('lines');

        if ($cart->lines->isEmpty()) {
            throw new \InvalidArgumentException('Cannot checkout an empty cart.');
        }

        Validator::make(['email' => $email], ['email' => ['required', 'email']])->validate();

        return Checkout::create(['store_id' => $cart->store_id, 'cart_id' => $cart->getKey(), 'customer_id' => $customer?->getKey(), 'status' => CheckoutStatus::Started, 'email' => $email, 'discount_code' => $cart->discount_code, 'expires_at' => now()->addHours(24)]);
    }

    public function setAddress(Checkout $checkout, array $address, ?array $billing = null, bool $useShippingAsBilling = true): Checkout
    {
        if (in_array($checkout->status, [CheckoutStatus::PaymentSelected, CheckoutStatus::PaymentPending, CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
            throw new \LogicException('This checkout can no longer be changed.');
        }

        Validator::make($address, ['first_name' => ['required', 'string', 'max:255'], 'last_name' => ['required', 'string', 'max:255'], 'address1' => ['required', 'string', 'max:500'], 'city' => ['required', 'string', 'max:255'], 'country_code' => ['required', 'string', 'size:2'], 'postal_code' => ['required', 'string', 'max:20']])->validate();
        $checkout->update(['shipping_address_json' => $address, 'billing_address_json' => $useShippingAsBilling ? $address : $billing, 'status' => CheckoutStatus::Addressed]);
        $this->pricing->calculate($checkout->refresh());
        CheckoutAddressed::dispatch($checkout);

        return $checkout->refresh();
    }

    public function setShippingMethod(Checkout $checkout, int $rateId): Checkout
    {
        $checkout->loadMissing('cart.lines.variant');

        if (! in_array($checkout->status, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected], true)) {
            throw new \LogicException('Checkout must have an address before selecting shipping.');
        }

        if (! $this->requiresShipping($checkout)) {
            $checkout->update(['shipping_rate_id' => null, 'shipping_method_id' => null, 'status' => CheckoutStatus::ShippingSelected]);
            $this->pricing->calculate($checkout->refresh());
            CheckoutShippingSelected::dispatch($checkout);

            return $checkout->refresh();
        }

        if ($checkout->shipping_address_json === null) {
            throw new \LogicException('An address is required before selecting shipping.');
        }

        $rate = $checkout->shipping_address_json === null ? null : $this->shipping->getAvailableRates($checkout->store, $checkout->shipping_address_json)->firstWhere('id', $rateId);

        if ($rate === null) {
            throw new \InvalidArgumentException('The selected shipping method is unavailable.');
        }

        $checkout->update(['shipping_rate_id' => $rate->getKey(), 'shipping_method_id' => $rate->getKey(), 'status' => CheckoutStatus::ShippingSelected]);
        $this->pricing->calculate($checkout->refresh());
        CheckoutShippingSelected::dispatch($checkout);

        return $checkout->refresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $method): Checkout
    {
        Validator::make(['method' => $method], ['method' => ['required', 'in:credit_card,paypal,bank_transfer']])->validate();

        $checkout->loadMissing('cart.lines.variant');

        if ($checkout->shipping_address_json === null) {
            throw new \LogicException('An address is required before selecting payment.');
        }

        if ($this->requiresShipping($checkout) && $checkout->shipping_rate_id === null) {
            throw new \LogicException('A shipping method is required before selecting payment.');
        }

        if ($checkout->status !== CheckoutStatus::ShippingSelected) {
            throw new \LogicException('Checkout must have a selected shipping method before selecting payment.');
        }

        $checkout->load('cart.lines.variant.inventory');

        DB::transaction(function () use ($checkout, $method): void {
            foreach ($checkout->cart->lines as $line) {
                if ($line->variant->inventory !== null) {
                    $this->inventory->reserve($line->variant->inventory, $line->quantity);
                }
            }

            $checkout->update(['payment_method' => $method, 'status' => CheckoutStatus::PaymentSelected, 'expires_at' => now()->addHours(24)]);
        });

        return $checkout->refresh();
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::Expired || $checkout->status === CheckoutStatus::Completed) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $checkout->load('cart.lines.variant.inventory');

                foreach ($checkout->cart->lines as $line) {
                    if ($line->variant->inventory !== null) {
                        $this->inventory->release($line->variant->inventory, $line->quantity);
                    }
                }
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);
            CheckoutExpired::dispatch($checkout->refresh());
        });
    }

    private function requiresShipping(Checkout $checkout): bool
    {
        return $checkout->cart->lines->contains(fn ($line): bool => (bool) $line->variant->requires_shipping);
    }
}
