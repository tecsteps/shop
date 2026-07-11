<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutCompleted;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly InventoryService $inventoryService,
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {}

    public function create(Cart $cart): Checkout
    {
        if (! $cart->lines()->exists()) {
            throw ValidationException::withMessages(['cart' => 'The cart is empty.']);
        }

        return Checkout::query()->create(['store_id' => $cart->store_id, 'cart_id' => $cart->id, 'customer_id' => $cart->customer_id]);
    }

    /** @param array<string, mixed> $data */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->requireStatus($checkout, CheckoutStatus::Started, CheckoutStatus::Addressed);

        $validated = Validator::make($data, [
            'email' => ['required', 'email'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.address1' => ['required', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string', 'max:32'],
            'billing_address' => ['sometimes', 'array'],
        ])->validate();

        $checkout->update([
            'email' => $validated['email'],
            'shipping_address_json' => $validated['shipping_address'],
            'billing_address_json' => $validated['billing_address'] ?? $validated['shipping_address'],
            'status' => CheckoutStatus::Addressed,
        ]);
        $this->pricingEngine->calculate($checkout->refresh());
        CheckoutAddressed::dispatch($checkout);

        return $checkout->refresh();
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->requireStatus($checkout, CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected);
        $checkout->loadMissing('cart.lines.variant');
        $requiresShipping = $checkout->cart->lines->contains(fn ($line): bool => $line->variant->requires_shipping);

        if ($requiresShipping) {
            $availableRates = $this->shippingCalculator->getAvailableRates($checkout->store, $checkout->shipping_address_json ?? []);

            if (! $shippingRateId || ! $availableRates->contains('id', $shippingRateId)) {
                throw ValidationException::withMessages(['shipping_method_id' => 'Select an available shipping method.']);
            }
        }

        $checkout->update(['shipping_method_id' => $requiresShipping ? $shippingRateId : null, 'status' => CheckoutStatus::ShippingSelected]);
        $this->pricingEngine->calculate($checkout->refresh());
        CheckoutShippingSelected::dispatch($checkout);

        return $checkout->refresh();
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod|string $paymentMethod): Checkout
    {
        $this->requireStatus($checkout, CheckoutStatus::ShippingSelected);
        $paymentMethod = is_string($paymentMethod) ? PaymentMethod::from($paymentMethod) : $paymentMethod;

        DB::transaction(function () use ($checkout, $paymentMethod): void {
            foreach ($checkout->cart->lines()->with('variant.inventoryItem')->get() as $line) {
                $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
            }

            $checkout->update(['payment_method' => $paymentMethod, 'status' => CheckoutStatus::PaymentSelected, 'expires_at' => now()->addDay()]);
        });

        return $checkout->refresh();
    }

    /** @param array<string, mixed> $paymentDetails */
    public function completeCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        if ($existingOrder = $checkout->order()->first()) {
            return $existingOrder;
        }

        $this->requireStatus($checkout, CheckoutStatus::PaymentSelected);

        try {
            $paymentResult = $this->paymentService->charge($checkout, $paymentDetails);
            $order = $this->orderService->createFromCheckout($checkout, $paymentResult);
        } catch (PaymentFailedException $exception) {
            foreach ($checkout->cart->lines()->with('variant.inventoryItem')->get() as $line) {
                $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
            }

            $checkout->update(['status' => CheckoutStatus::ShippingSelected, 'expires_at' => null]);
            throw $exception;
        }

        $checkout->update(['status' => CheckoutStatus::Completed]);
        CheckoutCompleted::dispatch($checkout, $order);

        return $order;
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
            return;
        }

        if ($checkout->status === CheckoutStatus::PaymentSelected) {
            foreach ($checkout->cart->lines()->with('variant.inventoryItem')->get() as $line) {
                $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
            }
        }

        $checkout->update(['status' => CheckoutStatus::Expired]);
        CheckoutExpired::dispatch($checkout);
    }

    private function requireStatus(Checkout $checkout, CheckoutStatus ...$statuses): void
    {
        if (! in_array($checkout->status, $statuses, true)) {
            throw new InvalidCheckoutTransitionException("Checkout cannot transition from {$checkout->status->value}.");
        }
    }
}
