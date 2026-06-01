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
use App\Models\ShippingRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Drives the checkout state machine:
 * started -> addressed -> shipping_selected -> payment_selected -> completed,
 * plus the terminal `expired` transition from any active state.
 *
 * Pricing is recalculated whenever the address, shipping method, or discount
 * changes. `selectPaymentMethod` reserves inventory and sets the expiry;
 * `completeCheckout` is idempotent (a second call returns the existing order).
 */
class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricing,
        private readonly ShippingCalculator $shipping,
        private readonly InventoryService $inventory,
        private readonly PaymentService $payments,
        private readonly OrderService $orders,
        private readonly DiscountService $discounts,
    ) {}

    /**
     * Start a checkout from a non-empty cart.
     */
    public function startFromCart(Cart $cart): Checkout
    {
        if ($cart->lines()->count() === 0) {
            throw ValidationException::withMessages(['cart' => 'Cannot start a checkout for an empty cart.']);
        }

        $checkout = Checkout::create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started->value,
        ]);

        $this->pricing->calculate($checkout);

        return $checkout->refresh();
    }

    /**
     * Transition started -> addressed: save the email and shipping address,
     * copy it to billing by default, and recalculate pricing.
     *
     * @param  array<string, mixed>  $data  email + shipping_address fields
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Started, CheckoutStatus::Addressed]);

        $validated = $this->validateAddress($data);

        $checkout->update([
            'email' => $validated['email'],
            'shipping_address_json' => $validated['shipping_address'],
            'billing_address_json' => $validated['billing_address'] ?? $validated['shipping_address'],
            'status' => CheckoutStatus::Addressed->value,
        ]);

        $this->pricing->calculate($checkout);

        CheckoutAddressed::dispatch($checkout->refresh());

        return $checkout;
    }

    /**
     * Transition addressed -> shipping_selected.
     *
     * When no cart line requires shipping the step is skipped: shipping method
     * is null and shipping cost is zero. Otherwise the chosen rate must belong
     * to a zone matching the checkout's address.
     */
    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected]);

        $cart = $checkout->cart->loadMissing('lines.variant');

        if (! $this->shipping->cartRequiresShipping($cart)) {
            $checkout->update([
                'shipping_method_id' => null,
                'status' => CheckoutStatus::ShippingSelected->value,
            ]);
            $this->pricing->calculate($checkout);
            CheckoutShippingSelected::dispatch($checkout->refresh());

            return $checkout;
        }

        $this->assertRateApplicable($checkout, $shippingRateId);

        $checkout->update([
            'shipping_method_id' => $shippingRateId,
            'status' => CheckoutStatus::ShippingSelected->value,
        ]);

        $this->pricing->calculate($checkout);

        CheckoutShippingSelected::dispatch($checkout->refresh());

        return $checkout;
    }

    /**
     * Transition shipping_selected -> payment_selected: store the payment
     * method, reserve inventory for every line, and set the 24h expiry.
     */
    public function selectPaymentMethod(Checkout $checkout, PaymentMethod $method): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected]);

        return DB::transaction(function () use ($checkout, $method): Checkout {
            $this->reserveInventory($checkout);

            $checkout->update([
                'payment_method' => $method->value,
                'expires_at' => Carbon::now()->addHours((int) config('shop.checkout_expiry_hours', 24)),
                'status' => CheckoutStatus::PaymentSelected->value,
            ]);

            return $checkout->refresh();
        });
    }

    /**
     * Apply (or clear) a discount code and recalculate pricing.
     */
    public function applyDiscountCode(Checkout $checkout, ?string $code): Checkout
    {
        $checkout->update(['discount_code' => $code === null || $code === '' ? null : $code]);
        $this->pricing->calculate($checkout);

        return $checkout->refresh();
    }

    /**
     * Validate a discount code against a checkout's cart, throwing
     * {@see \App\Exceptions\InvalidDiscountException} with a reason on failure.
     * Lets storefront UI surface a specific error before applying.
     */
    public function validateDiscountForCheckout(string $code, Checkout $checkout): void
    {
        $this->discounts->validate($code, $checkout->store, $checkout->cart->loadMissing('lines.variant.product'));
    }

    /**
     * Transition payment_selected -> completed. Idempotent: a second call for a
     * checkout that already has an order returns that order without re-charging.
     *
     * @param  array<string, mixed>  $paymentMethodData
     *
     * @throws PaymentFailedException
     */
    public function completeCheckout(Checkout $checkout, array $paymentMethodData = []): Order
    {
        // Idempotency: if this checkout already produced an order (recorded on
        // the checkout) return it without re-charging, even after the checkout
        // has transitioned to completed.
        $existing = $this->existingOrderFor($checkout);

        if ($existing !== null) {
            return $existing;
        }

        $this->assertStatus($checkout, [CheckoutStatus::PaymentSelected]);

        // Re-check after the status guard in case a concurrent request created
        // the order between the lookup and here.
        $existing = $this->existingOrderFor($checkout);

        if ($existing !== null) {
            return $existing;
        }

        $this->pricing->calculate($checkout);

        $result = $this->payments->charge($checkout, $paymentMethodData);

        if (! $result->success) {
            $this->releaseInventory($checkout);

            throw new PaymentFailedException($result->errorCode ?? 'payment_failed', $result->errorMessage ?? 'Payment failed.');
        }

        $order = $this->orders->createFromCheckout($checkout, $result);

        CheckoutCompleted::dispatch($checkout->refresh());

        return $order;
    }

    /**
     * Transition any active state -> expired, releasing reserved inventory when
     * the checkout had progressed to payment_selected.
     */
    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $this->releaseInventory($checkout);
            }

            $checkout->update(['status' => CheckoutStatus::Expired->value]);
        });

        CheckoutExpired::dispatch($checkout->refresh());
    }

    private function existingOrderFor(Checkout $checkout): ?Order
    {
        return Order::query()
            ->where('store_id', $checkout->store_id)
            ->where('checkout_id', $checkout->id)
            ->first();
    }

    private function reserveInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->loadMissing('lines.variant.inventoryItem')->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->reserve($item, $line->quantity);
            }
        }
    }

    private function releaseInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->loadMissing('lines.variant.inventoryItem')->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }

    private function assertRateApplicable(Checkout $checkout, ?int $shippingRateId): void
    {
        if ($shippingRateId === null) {
            throw ValidationException::withMessages(['shipping' => 'A shipping method is required.']);
        }

        $available = $this->shipping->getAvailableRates($checkout->store, $checkout->shipping_address_json ?? []);

        if (! $available->contains('id', $shippingRateId)) {
            throw ValidationException::withMessages([
                'shipping' => 'The selected shipping method is not available for this address.',
            ]);
        }

        if (ShippingRate::query()->find($shippingRateId) === null) {
            throw ValidationException::withMessages(['shipping' => 'The selected shipping method does not exist.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{email: string, shipping_address: array<string, mixed>, billing_address?: array<string, mixed>}
     */
    private function validateAddress(array $data): array
    {
        $validator = validator($data, [
            'email' => ['required', 'email'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string'],
            'shipping_address.last_name' => ['required', 'string'],
            'shipping_address.address1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string'],
        ]);

        return $validator->validate();
    }

    /**
     * @param  list<CheckoutStatus>  $allowed
     *
     * @throws InvalidCheckoutTransitionException
     */
    private function assertStatus(Checkout $checkout, array $allowed): void
    {
        if (! in_array($checkout->status, $allowed, true)) {
            $expected = implode(', ', array_map(fn (CheckoutStatus $s): string => $s->value, $allowed));
            throw new InvalidCheckoutTransitionException(
                "Invalid checkout transition from '{$checkout->status->value}' (expected one of: {$expected}).",
            );
        }
    }
}
