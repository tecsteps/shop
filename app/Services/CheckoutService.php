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
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        public PricingEngine $pricingEngine,
        public InventoryService $inventoryService,
        public ShippingCalculator $shippingCalculator,
        public PaymentProvider $paymentProvider,
        public OrderService $orderService,
    ) {}

    public function createCheckout(Cart $cart): Checkout
    {
        if ($cart->lines()->count() === 0) {
            throw new \InvalidArgumentException('Cannot create checkout for an empty cart.');
        }

        $existingCompleted = Checkout::withoutGlobalScopes()
            ->where('cart_id', $cart->id)
            ->where('status', CheckoutStatus::Completed)
            ->first();

        if ($existingCompleted) {
            throw new \InvalidArgumentException('A completed checkout already exists for this cart.');
        }

        /** @var Store $store */
        $store = $cart->store()->withoutGlobalScopes()->firstOrFail();

        $checkout = Checkout::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
        ]);

        return $checkout;
    }

    /**
     * @param  array{email: string, shipping_address: array<string, mixed>, billing_address?: array<string, mixed>}  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::Addressed);

        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required.');
        }

        if (empty($data['shipping_address'])) {
            throw new \InvalidArgumentException('Shipping address is required.');
        }

        $requiredFields = ['first_name', 'last_name', 'address1', 'city', 'country_code', 'postal_code'];
        foreach ($requiredFields as $field) {
            if (empty($data['shipping_address'][$field])) {
                throw new \InvalidArgumentException("Shipping address field '{$field}' is required.");
            }
        }

        $checkout->update([
            'email' => $data['email'],
            'shipping_address_json' => $data['shipping_address'],
            'billing_address_json' => $data['billing_address'] ?? $data['shipping_address'],
            'status' => CheckoutStatus::Addressed,
        ]);

        /** @var Checkout $freshCheckout */
        $freshCheckout = $checkout->fresh();
        $this->pricingEngine->calculate($freshCheckout);

        /** @var Checkout */
        return $checkout->fresh();
    }

    public function setShippingMethod(Checkout $checkout, int $rateId): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::ShippingSelected);

        $rate = ShippingRate::withoutGlobalScopes()->findOrFail($rateId);

        /** @var Store $store */
        $store = $checkout->store()->withoutGlobalScopes()->firstOrFail();
        /** @var array{country_code?: string, province_code?: string} $address */
        $address = $checkout->shipping_address_json ?? [];
        $availableRates = $this->shippingCalculator->getAvailableRates($store, $address);

        if (! $availableRates->contains('id', $rate->id)) {
            throw new \InvalidArgumentException('Shipping rate is not available for this address.');
        }

        $checkout->update([
            'shipping_method_id' => $rateId,
            'status' => CheckoutStatus::ShippingSelected,
        ]);

        /** @var Checkout $freshCheckout */
        $freshCheckout = $checkout->fresh();
        $this->pricingEngine->calculate($freshCheckout);

        /** @var Checkout */
        return $checkout->fresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $method): Checkout
    {
        $this->assertTransition($checkout, CheckoutStatus::PaymentSelected);

        $validMethods = ['credit_card', 'paypal', 'bank_transfer'];
        if (! in_array($method, $validMethods)) {
            throw new \InvalidArgumentException('Invalid payment method.');
        }

        return DB::transaction(function () use ($checkout, $method): Checkout {
            $checkout->load('cart.lines.variant.inventoryItem');

            /** @var Cart $cart */
            $cart = $checkout->cart;
            foreach ($cart->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
                }
            }

            $checkout->update([
                'payment_method' => $method,
                'expires_at' => now()->addHours(24),
                'status' => CheckoutStatus::PaymentSelected,
            ]);

            /** @var Checkout */
            return $checkout->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function completeCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        // Idempotency: if already completed, return existing order
        if ($checkout->status === CheckoutStatus::Completed) {
            $existingOrder = Order::withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->where('email', $checkout->email)
                ->whereHas('payments', function ($q): void {
                    $q->where('order_id', '>', 0);
                })
                ->latest()
                ->first();

            if ($existingOrder) {
                return $existingOrder;
            }
        }

        $this->assertTransition($checkout, CheckoutStatus::Completed);

        $paymentMethod = PaymentMethod::from((string) $checkout->payment_method);

        $paymentResult = $this->paymentProvider->charge($checkout, $paymentMethod, $paymentDetails);

        if (! $paymentResult->success) {
            $checkout->load('cart.lines.variant.inventoryItem');

            /** @var Cart $failedCart */
            $failedCart = $checkout->cart;
            foreach ($failedCart->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            throw new PaymentFailedException(
                errorCode: $paymentResult->errorCode ?? 'unknown',
                message: "Payment failed with error: {$paymentResult->errorCode}",
            );
        }

        return $this->orderService->createFromCheckout($checkout, $paymentResult);
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::PaymentSelected) {
            $checkout->load('cart.lines.variant.inventoryItem');

            /** @var Cart $expireCart */
            $expireCart = $checkout->cart;
            foreach ($expireCart->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }
        }

        $checkout->update([
            'status' => CheckoutStatus::Expired,
        ]);
    }

    private function assertTransition(Checkout $checkout, CheckoutStatus $target): void
    {
        $allowed = match ($checkout->status) {
            CheckoutStatus::Started => [CheckoutStatus::Addressed, CheckoutStatus::Expired],
            CheckoutStatus::Addressed => [CheckoutStatus::ShippingSelected, CheckoutStatus::Expired],
            CheckoutStatus::ShippingSelected => [CheckoutStatus::PaymentSelected, CheckoutStatus::Expired],
            CheckoutStatus::PaymentSelected => [CheckoutStatus::Completed, CheckoutStatus::Expired],
            CheckoutStatus::Completed => [],
            CheckoutStatus::Expired => [],
        };

        if (! in_array($target, $allowed)) {
            throw new InvalidCheckoutTransitionException(
                "Cannot transition from {$checkout->status->value} to {$target->value}."
            );
        }
    }
}
