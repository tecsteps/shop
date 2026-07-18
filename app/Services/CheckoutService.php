<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\ShippingRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly InventoryService $inventoryService,
        private readonly PaymentProvider $paymentProvider,
        private readonly OrderService $orderService,
    ) {}

    public function create(Cart $cart): Checkout
    {
        return Checkout::query()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertStatus($checkout, CheckoutStatus::Started);
        $validated = Validator::make($data, [
            'email' => ['required', 'email'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string'],
            'shipping_address.last_name' => ['required', 'string'],
            'shipping_address.address1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string'],
        ])->validate();

        $checkout->update([
            'email' => $validated['email'],
            'shipping_address_json' => $validated['shipping_address'],
            'billing_address_json' => $validated['shipping_address'],
            'status' => CheckoutStatus::Addressed,
        ]);
        $this->pricingEngine->calculate($checkout);

        return $checkout->refresh();
    }

    public function setShippingMethod(Checkout $checkout, ?int $rateId): Checkout
    {
        $this->assertStatus($checkout, CheckoutStatus::Addressed);
        $checkout->loadMissing('cart.lines.variant');
        $requiresShipping = $checkout->cart->lines->contains(fn ($line): bool => $line->variant->requires_shipping);

        if (! $requiresShipping) {
            $checkout->update(['shipping_method_id' => null, 'status' => CheckoutStatus::ShippingSelected]);
            $this->pricingEngine->calculate($checkout);

            return $checkout->refresh();
        }

        $availableIds = $this->shippingCalculator
            ->getAvailableRates($checkout->store, $checkout->shipping_address_json)
            ->pluck('id');
        $rate = ShippingRate::query()->findOrFail($rateId);

        if (! $availableIds->contains($rate->id)) {
            throw new InvalidCheckoutTransitionException('Cannot ship to this address.');
        }

        $checkout->update(['shipping_method_id' => $rate->id, 'status' => CheckoutStatus::ShippingSelected]);
        $this->pricingEngine->calculate($checkout);

        return $checkout->refresh();
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod|string $method): Checkout
    {
        $this->assertStatus($checkout, CheckoutStatus::ShippingSelected);
        $method = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);

        return DB::transaction(function () use ($checkout, $method): Checkout {
            $checkout->loadMissing('cart.lines.variant.inventoryItem');

            foreach ($checkout->cart->lines as $line) {
                $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
            }

            $checkout->update([
                'payment_method' => $method,
                'status' => CheckoutStatus::PaymentSelected,
                'expires_at' => now()->addDay(),
            ]);

            return $checkout->refresh();
        });
    }

    /** @param array<string, mixed> $paymentData */
    public function completeCheckout(Checkout $checkout, array $paymentData = []): Order
    {
        if ($checkout->order()->exists()) {
            return $checkout->order;
        }

        $this->assertStatus($checkout, CheckoutStatus::PaymentSelected);

        return DB::transaction(function () use ($checkout, $paymentData): Order {
            $result = $this->paymentProvider->charge($checkout, $checkout->payment_method, $paymentData);

            if (! $result->success) {
                $checkout->loadMissing('cart.lines.variant.inventoryItem');

                foreach ($checkout->cart->lines as $line) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }

                $checkout->update([
                    'payment_method' => null,
                    'status' => CheckoutStatus::ShippingSelected,
                    'expires_at' => null,
                ]);

                throw new PaymentFailedException($result->errorCode ?? 'payment_failed');
            }

            $order = $this->orderService->createFromCheckout($checkout, $result);
            $checkout->cart->update(['status' => CartStatus::Converted]);
            $checkout->update(['status' => CheckoutStatus::Completed]);

            return $order;
        });
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $checkout->loadMissing('cart.lines.variant.inventoryItem');

                foreach ($checkout->cart->lines as $line) {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                }
            }

            $checkout->update(['status' => CheckoutStatus::Expired]);
        });
    }

    private function assertStatus(Checkout $checkout, CheckoutStatus $expected): void
    {
        if ($checkout->status !== $expected) {
            throw new InvalidCheckoutTransitionException(
                "Checkout must be {$expected->value}; current status is {$checkout->status->value}."
            );
        }
    }
}
