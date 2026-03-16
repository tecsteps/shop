<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        protected PricingEngine $pricingEngine,
        protected ShippingCalculator $shippingCalculator,
        protected InventoryService $inventoryService
    ) {}

    public function createFromCart(Cart $cart): Checkout
    {
        return DB::transaction(function () use ($cart) {
            return Checkout::query()->create([
                'store_id' => $cart->store_id,
                'cart_id' => $cart->id,
                'customer_id' => $cart->customer_id,
                'status' => CheckoutStatus::Started,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    public function setAddress(Checkout $checkout, array $addressData): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Started, CheckoutStatus::Addressed]);

        return DB::transaction(function () use ($checkout, $addressData) {
            $shippingAddress = [
                'first_name' => $addressData['shipping_address']['first_name'],
                'last_name' => $addressData['shipping_address']['last_name'],
                'address1' => $addressData['shipping_address']['address1'],
                'address2' => $addressData['shipping_address']['address2'] ?? null,
                'company' => $addressData['shipping_address']['company'] ?? null,
                'city' => $addressData['shipping_address']['city'],
                'province' => $addressData['shipping_address']['province'] ?? null,
                'province_code' => $addressData['shipping_address']['province_code'] ?? null,
                'country' => $addressData['shipping_address']['country'],
                'postal_code' => $addressData['shipping_address']['postal_code'],
                'phone' => $addressData['shipping_address']['phone'] ?? null,
            ];

            $billingAddress = $addressData['billing_address'] ?? $shippingAddress;

            $checkout->update([
                'email' => $addressData['email'],
                'shipping_address_json' => $shippingAddress,
                'billing_address_json' => $billingAddress,
                'status' => CheckoutStatus::Addressed,
            ]);

            $this->pricingEngine->calculate($checkout->fresh());

            CheckoutAddressed::dispatch($checkout);

            return $checkout->fresh();
        });
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected]);

        return DB::transaction(function () use ($checkout, $shippingRateId) {
            $cart = $checkout->cart()->with('lines.variant')->first();

            $requiresShipping = false;
            foreach ($cart->lines as $line) {
                if ($line->variant && $line->variant->requires_shipping) {
                    $requiresShipping = true;
                    break;
                }
            }

            if (! $requiresShipping) {
                $checkout->update([
                    'shipping_method_id' => null,
                    'status' => CheckoutStatus::ShippingSelected,
                ]);

                $this->pricingEngine->calculate($checkout->fresh());

                CheckoutShippingSelected::dispatch($checkout);

                return $checkout->fresh();
            }

            if ($shippingRateId) {
                $rate = ShippingRate::query()->findOrFail($shippingRateId);

                $address = $checkout->shipping_address_json ?? [];
                $store = $checkout->store;
                $zone = $this->shippingCalculator->getMatchingZone($store, $address);

                if (! $zone || $rate->zone_id !== $zone->id) {
                    throw new InvalidCheckoutTransitionException('Selected shipping rate does not apply to this address.');
                }
            }

            $checkout->update([
                'shipping_method_id' => $shippingRateId,
                'status' => CheckoutStatus::ShippingSelected,
            ]);

            $this->pricingEngine->calculate($checkout->fresh());

            CheckoutShippingSelected::dispatch($checkout);

            return $checkout->fresh();
        });
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected]);

        $validMethods = ['credit_card', 'paypal', 'bank_transfer'];

        if (! in_array($paymentMethod, $validMethods)) {
            throw new InvalidCheckoutTransitionException("Invalid payment method: {$paymentMethod}");
        }

        return DB::transaction(function () use ($checkout, $paymentMethod) {
            $cart = $checkout->cart()->with('lines.variant')->first();

            foreach ($cart->lines as $line) {
                if (! $line->variant) {
                    continue;
                }

                $inventoryItem = InventoryItem::query()
                    ->withoutGlobalScopes()
                    ->where('variant_id', $line->variant_id)
                    ->first();

                if ($inventoryItem) {
                    $this->inventoryService->reserve($inventoryItem, $line->quantity);
                }
            }

            $checkout->update([
                'payment_method' => $paymentMethod,
                'status' => CheckoutStatus::PaymentSelected,
                'expires_at' => now()->addHours(24),
            ]);

            return $checkout->fresh();
        });
    }

    public function expireCheckout(Checkout $checkout): void
    {
        $activeStatuses = [
            CheckoutStatus::Started,
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
            CheckoutStatus::PaymentSelected,
        ];

        if (! in_array($checkout->status, $activeStatuses)) {
            return;
        }

        DB::transaction(function () use ($checkout) {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $this->releaseReservedInventory($checkout);
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);

            CheckoutExpired::dispatch($checkout);
        });
    }

    protected function releaseReservedInventory(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant')->first();

        foreach ($cart->lines as $line) {
            if (! $line->variant) {
                continue;
            }

            $inventoryItem = InventoryItem::query()
                ->withoutGlobalScopes()
                ->where('variant_id', $line->variant_id)
                ->first();

            if ($inventoryItem) {
                $this->inventoryService->release($inventoryItem, $line->quantity);
            }
        }
    }

    /**
     * @param  array<CheckoutStatus>  $allowedStatuses
     */
    protected function assertStatus(Checkout $checkout, array $allowedStatuses): void
    {
        if (! in_array($checkout->status, $allowedStatuses)) {
            throw new InvalidCheckoutTransitionException(
                "Cannot perform this action on checkout with status '{$checkout->status->value}'."
            );
        }
    }
}
