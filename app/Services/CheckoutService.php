<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutCompleted;
use App\Events\CheckoutShippingSelected;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private InventoryService $inventoryService,
        private PaymentProvider $paymentProvider,
        private OrderService $orderService,
    ) {}

    /**
     * Transition: started -> addressed.
     *
     * @param  array{email: string, shipping_address: array<string, mixed>, billing_address?: array<string, mixed>}  $addressData
     */
    public function setAddress(Checkout $checkout, array $addressData): Checkout
    {
        if ($checkout->status !== CheckoutStatus::Started) {
            throw new \InvalidArgumentException('Checkout must be in started state to set address.');
        }

        $shippingAddress = $addressData['shipping_address'];
        $billingAddress = $addressData['billing_address'] ?? $shippingAddress;

        $checkout->update([
            'email' => $addressData['email'],
            'shipping_address_json' => $shippingAddress,
            'billing_address_json' => $billingAddress,
            'status' => CheckoutStatus::Addressed,
        ]);

        CheckoutAddressed::dispatch($checkout->id);

        return $checkout->refresh();
    }

    /**
     * Transition: addressed -> shipping_selected.
     */
    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        if ($checkout->status !== CheckoutStatus::Addressed) {
            throw new \InvalidArgumentException('Checkout must be in addressed state to set shipping method.');
        }

        $checkout->update([
            'shipping_method_id' => $shippingRateId,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        CheckoutShippingSelected::dispatch($checkout->id);

        return $checkout->refresh();
    }

    /**
     * Transition: shipping_selected -> payment_selected.
     * Reserves inventory for all cart lines.
     */
    public function selectPaymentMethod(Checkout $checkout, PaymentMethod $paymentMethod): Checkout
    {
        if ($checkout->status !== CheckoutStatus::ShippingSelected) {
            throw new \InvalidArgumentException('Checkout must be in shipping_selected state to select payment method.');
        }

        DB::transaction(function () use ($checkout, $paymentMethod) {
            $cart = $checkout->cart;

            foreach ($cart->lines as $line) {
                $inventoryItem = $line->variant->inventoryItem;

                if ($inventoryItem) {
                    $this->inventoryService->reserve($inventoryItem, $line->quantity);
                }
            }

            $checkout->update([
                'payment_method' => $paymentMethod,
                'expires_at' => now()->addHours(24),
                'status' => CheckoutStatus::PaymentSelected,
            ]);
        });

        return $checkout->refresh();
    }

    /**
     * Complete checkout: charge payment and create order.
     * Handles idempotency - if checkout is already completed, returns existing order.
     *
     * @param  array<string, mixed>  $paymentDetails
     */
    public function completeCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            $order = Order::where('store_id', $checkout->store_id)
                ->whereHas('payments', fn ($q) => $q->where('order_id', '>', 0))
                ->latest()
                ->firstOrFail();

            return $order;
        }

        if ($checkout->status !== CheckoutStatus::PaymentSelected) {
            throw new \InvalidArgumentException('Checkout must be in payment_selected state to complete.');
        }

        $paymentResult = $this->paymentProvider->charge(
            $checkout,
            $checkout->payment_method,
            $paymentDetails,
        );

        if (! $paymentResult->success) {
            $this->releaseInventoryForCheckout($checkout);

            throw new \RuntimeException(
                $paymentResult->errorMessage ?? 'Payment failed: '.($paymentResult->errorCode ?? 'unknown error')
            );
        }

        $order = $this->orderService->createFromCheckout($checkout, $paymentResult);

        CheckoutCompleted::dispatch($checkout->id, $order->id);

        return $order;
    }

    /**
     * Transition: any active state -> expired.
     * Releases reserved inventory if status was payment_selected.
     */
    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired])) {
            return;
        }

        DB::transaction(function () use ($checkout) {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $this->releaseInventoryForCheckout($checkout);
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);
        });

        \App\Events\CheckoutExpired::dispatch($checkout->id);
    }

    /**
     * Release all reserved inventory for a checkout's cart lines.
     */
    private function releaseInventoryForCheckout(Checkout $checkout): void
    {
        $cart = $checkout->cart;

        foreach ($cart->lines as $line) {
            $inventoryItem = $line->variant->inventoryItem;

            if ($inventoryItem) {
                $this->inventoryService->release($inventoryItem, $line->quantity);
            }
        }
    }
}
