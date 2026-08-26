<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly InventoryService $inventoryService,
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {}

    public function create(Cart $cart, string $email): Checkout
    {
        if ($cart->lines()->count() === 0) {
            throw new InvalidArgumentException('Cannot checkout an empty cart.');
        }

        return DB::transaction(function () use ($cart, $email) {
            $checkout = Checkout::create([
                'store_id' => $cart->store_id,
                'cart_id' => $cart->id,
                'customer_id' => $cart->customer_id,
                'email' => $email,
                'status' => CheckoutStatus::Started->value,
            ]);

            $checkout->update(['totals_json' => $this->pricingEngine->calculate($checkout)->toArray()]);

            return $checkout;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Started, CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected]);

        $shipping = $data['shipping_address'] ?? null;
        $billing = $data['billing_address'] ?? $shipping;

        $checkout->update([
            'email' => $data['email'] ?? $checkout->email,
            'shipping_address_json' => $shipping,
            'billing_address_json' => $billing,
        ]);

        $checkout->update(['totals_json' => $this->pricingEngine->calculate($checkout)->toArray()]);
        $checkout->update(['status' => CheckoutStatus::Addressed->value]);

        CheckoutAddressed::dispatch($checkout);

        return $checkout->fresh();
    }

    public function setShippingMethod(Checkout $checkout, int $rateId): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Addressed]);

        if ($rateId > 0) {
            $rate = ShippingRate::findOrFail($rateId);
            $zone = $this->shippingCalculator->getMatchingZone($checkout->store, $checkout->shipping_address_json ?? []);

            if (! $zone || $rate->zone_id !== $zone->id) {
                throw new InvalidArgumentException('The selected shipping rate is not available for this address.');
            }
        }

        $checkout->update(['shipping_method_id' => $rateId > 0 ? $rateId : null]);
        $checkout->update(['totals_json' => $this->pricingEngine->calculate($checkout)->toArray()]);
        $checkout->update(['status' => CheckoutStatus::ShippingSelected->value]);

        CheckoutShippingSelected::dispatch($checkout);

        return $checkout->fresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $method): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::ShippingSelected]);

        $paymentMethod = PaymentMethod::from($method);

        DB::transaction(function () use ($checkout, $paymentMethod) {
            $this->reserveInventory($checkout);
            $checkout->update([
                'payment_method' => $paymentMethod->value,
                'status' => CheckoutStatus::PaymentSelected->value,
                'expires_at' => now()->addHours(24),
            ]);
        });

        return $checkout->fresh();
    }

    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function completeCheckout(Checkout $checkout, array $paymentData): Order
    {
        if ($checkout->status === CheckoutStatus::Completed->value) {
            return Order::where('checkout_id', $checkout->id)->first();
        }

        $this->assertStatus($checkout, [CheckoutStatus::PaymentSelected]);

        $method = PaymentMethod::from($checkout->payment_method);
        $result = $this->paymentService->charge($checkout, $method, $paymentData);

        if (! $result->success) {
            $this->releaseReservedInventory($checkout);
            $checkout->update(['status' => CheckoutStatus::ShippingSelected->value]);

            throw new PaymentFailedException($result->errorCode ?? 'payment_failed', $result->errorMessage ?? 'Payment failed.');
        }

        return $this->orderService->createFromCheckout($checkout, $result);
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value], true)) {
            return;
        }

        if ($checkout->status === CheckoutStatus::PaymentSelected->value) {
            $this->releaseReservedInventory($checkout);
        }

        $checkout->update(['status' => CheckoutStatus::Expired->value]);

        CheckoutExpired::dispatch($checkout);
    }

    private function reserveInventory(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();

        foreach ($cart->lines as $line) {
            $inventory = $line->variant->inventoryItem;

            if ($inventory) {
                $this->inventoryService->reserve($inventory, $line->quantity);
            }
        }
    }

    private function releaseReservedInventory(Checkout $checkout): void
    {
        $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();

        foreach ($cart->lines as $line) {
            $inventory = $line->variant->inventoryItem;

            if ($inventory) {
                $this->inventoryService->release($inventory, $line->quantity);
            }
        }
    }

    /**
     * @param  list<CheckoutStatus>  $allowed
     */
    private function assertStatus(Checkout $checkout, array $allowed): void
    {
        $current = CheckoutStatus::tryFrom($checkout->status);

        if ($current === null || ! in_array($current, $allowed, true)) {
            throw new InvalidCheckoutTransitionException("Invalid checkout transition from {$checkout->status}.");
        }
    }
}
