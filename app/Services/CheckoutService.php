<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
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
        private PricingEngine $pricingEngine,
        private InventoryService $inventoryService,
        private ShippingCalculator $shippingCalculator,
        private PaymentProvider $paymentProvider,
        private OrderService $orderService,
    ) {}

    public function createFromCart(Cart $cart): Checkout
    {
        if ($cart->lines()->count() === 0) {
            throw new InvalidArgumentException('Cannot create checkout from empty cart.');
        }

        return Checkout::withoutGlobalScopes()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
        ]);
    }

    public function setAddress(Checkout $checkout, array $data): void
    {
        if ($checkout->status !== CheckoutStatus::Started && $checkout->status !== CheckoutStatus::Addressed) {
            throw new InvalidCheckoutTransitionException(
                "Cannot set address from status {$checkout->status->value}."
            );
        }

        $checkout->update([
            'email' => $data['email'],
            'shipping_address_json' => $data['shipping_address'],
            'billing_address_json' => $data['billing_address'] ?? $data['shipping_address'],
            'status' => CheckoutStatus::Addressed,
        ]);

        $this->recalculatePricing($checkout);
    }

    public function setShippingMethod(Checkout $checkout, int $rateId): void
    {
        if ($checkout->status !== CheckoutStatus::Addressed && $checkout->status !== CheckoutStatus::ShippingSelected) {
            throw new InvalidCheckoutTransitionException(
                "Cannot set shipping from status {$checkout->status->value}."
            );
        }

        $rate = ShippingRate::findOrFail($rateId);
        $zone = $rate->zone;
        $address = $checkout->shipping_address_json ?? [];
        $country = $address['country'] ?? '';
        $countries = $zone->countries_json ?? [];

        if (! in_array($country, $countries)) {
            throw new InvalidArgumentException('Shipping rate does not apply to this address.');
        }

        $checkout->update([
            'shipping_method_id' => $rateId,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        $this->recalculatePricing($checkout);
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): void
    {
        $allowedStatuses = [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected];
        if (! in_array($checkout->status, $allowedStatuses)) {
            throw new InvalidCheckoutTransitionException(
                "Cannot select payment from status {$checkout->status->value}."
            );
        }

        $validMethods = ['credit_card', 'paypal', 'bank_transfer'];
        if (! in_array($paymentMethod, $validMethods)) {
            throw new InvalidArgumentException("Invalid payment method: {$paymentMethod}.");
        }

        $alreadySelected = $checkout->status === CheckoutStatus::PaymentSelected;

        DB::transaction(function () use ($checkout, $paymentMethod, $alreadySelected) {
            if (! $alreadySelected) {
                $cart = $checkout->cart()->with(['lines.variant.inventoryItem'])->first();

                foreach ($cart->lines as $line) {
                    if ($line->variant->inventoryItem) {
                        $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $checkout->update([
                'payment_method' => $paymentMethod,
                'expires_at' => now()->addHours(24),
                'status' => CheckoutStatus::PaymentSelected,
            ]);
        });
    }

    public function completeCheckout(Checkout $checkout, array $paymentData = []): Order
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            $existingOrder = Order::withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->whereHas('payments', fn ($q) => $q->where('order_id', '>', 0))
                ->latest()
                ->first();
            if ($existingOrder) {
                return $existingOrder;
            }
        }

        if ($checkout->status !== CheckoutStatus::PaymentSelected) {
            throw new InvalidCheckoutTransitionException(
                "Cannot complete checkout from status {$checkout->status->value}."
            );
        }

        $method = PaymentMethod::from($checkout->payment_method);
        $paymentResult = $this->paymentProvider->charge($checkout, $method, $paymentData);

        if (! $paymentResult->success) {
            $cart = $checkout->cart()->with(['lines.variant.inventoryItem'])->first();
            foreach ($cart->lines as $line) {
                if ($line->variant->inventoryItem && $line->variant->inventoryItem->quantity_reserved > 0) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            throw new PaymentFailedException(
                errorCode: $paymentResult->errorCode ?? 'unknown',
                message: $paymentResult->errorMessage ?? 'Payment failed.',
            );
        }

        return $this->orderService->createFromCheckout($checkout, $paymentResult);
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::Completed || $checkout->status === CheckoutStatus::Expired) {
            return;
        }

        DB::transaction(function () use ($checkout) {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $cart = $checkout->cart()->with(['lines.variant.inventoryItem'])->first();
                foreach ($cart->lines as $line) {
                    if ($line->variant->inventoryItem && $line->variant->inventoryItem->quantity_reserved > 0) {
                        $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);
        });
    }

    public function recalculatePublic(Checkout $checkout): void
    {
        $this->recalculatePricing($checkout);
    }

    private function recalculatePricing(Checkout $checkout): void
    {
        $result = $this->pricingEngine->calculate($checkout->fresh());
        $checkout->update(['totals_json' => $result->toArray()]);
    }
}
