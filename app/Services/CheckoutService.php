<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private ShippingCalculator $shippingCalculator,
        private InventoryService $inventoryService,
    ) {}

    public function createFromCart(Cart $cart): Checkout
    {
        return Checkout::create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
        ]);
    }

    /**
     * @param  array{email: string, shipping_address: array<string, mixed>, billing_address?: array<string, mixed>}  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::Addressed);

        $shippingAddress = $data['shipping_address'];
        $billingAddress = $data['billing_address'] ?? $shippingAddress;

        $checkout->update([
            'email' => $data['email'],
            'shipping_address_json' => $shippingAddress,
            'billing_address_json' => $billingAddress,
            'status' => CheckoutStatus::Addressed,
        ]);

        $this->recalculate($checkout);

        return $checkout->fresh();
    }

    public function setShippingMethod(Checkout $checkout, int $rateId): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::ShippingSelected);

        $rate = ShippingRate::findOrFail($rateId);

        // Verify the rate belongs to a zone matching the checkout address
        $store = $checkout->store;
        $address = $checkout->shipping_address_json ?? [];
        $zone = $this->shippingCalculator->getMatchingZone($store, $address);

        if (! $zone || $rate->zone_id !== $zone->id) {
            throw new \InvalidArgumentException('Shipping rate does not apply to the selected address');
        }

        $checkout->update([
            'shipping_method_id' => $rateId,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        $this->recalculate($checkout);

        return $checkout->fresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::PaymentSelected);

        if (! in_array($paymentMethod, ['credit_card', 'paypal', 'bank_transfer'])) {
            throw new \InvalidArgumentException('Invalid payment method');
        }

        return DB::transaction(function () use ($checkout, $paymentMethod) {
            $checkout->update([
                'payment_method' => $paymentMethod,
                'status' => CheckoutStatus::PaymentSelected,
                'expires_at' => now()->addHours(24),
            ]);

            // Reserve inventory for all cart lines
            $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
            foreach ($cart->lines as $line) {
                if ($line->variant->inventoryItem) {
                    $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
                }
            }

            return $checkout->fresh();
        });
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired])) {
            return;
        }

        DB::transaction(function () use ($checkout) {
            // Release inventory if it was reserved
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
                foreach ($cart->lines as $line) {
                    if ($line->variant->inventoryItem) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);
        });
    }

    private function recalculate(Checkout $checkout): void
    {
        $result = $this->pricingEngine->calculate($checkout->fresh());
        $checkout->update(['totals_json' => $result->toArray()]);
    }

    private function assertTransition(Checkout $checkout, CheckoutStatus $to): void
    {
        $allowed = match ($checkout->status) {
            CheckoutStatus::Started => [CheckoutStatus::Addressed],
            CheckoutStatus::Addressed => [CheckoutStatus::ShippingSelected],
            CheckoutStatus::ShippingSelected => [CheckoutStatus::PaymentSelected],
            CheckoutStatus::PaymentSelected => [CheckoutStatus::Completed],
            default => [],
        };

        if (! in_array($to, $allowed)) {
            throw new InvalidCheckoutTransitionException(
                from: $checkout->status->value,
                to: $to->value,
            );
        }
    }
}
