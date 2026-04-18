<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Exceptions\CheckoutStateException;
use App\Models\Cart;
use App\Models\Checkout;
use App\ValueObjects\PricingResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected PricingEngine $pricing,
        protected ShippingCalculator $shipping,
        protected InventoryService $inventory,
    ) {}

    public function startFromCart(Cart $cart): Checkout
    {
        return DB::transaction(function () use ($cart): Checkout {
            $existing = Checkout::query()
                ->where('cart_id', $cart->id)
                ->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])
                ->first();

            if ($existing) {
                return $existing;
            }

            return Checkout::create([
                'store_id' => $cart->store_id,
                'cart_id' => $cart->id,
                'customer_id' => $cart->customer_id,
                'status' => CheckoutStatus::Started,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->guardState($checkout, [
            CheckoutStatus::Started,
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
        ]);

        if (empty($data['email'])) {
            throw ValidationException::withMessages(['email' => 'Email is required.']);
        }

        $address = $data['shipping_address'] ?? [];
        foreach (['first_name', 'last_name', 'address1', 'city', 'country_code', 'zip'] as $field) {
            if (empty($address[$field])) {
                throw ValidationException::withMessages([
                    "shipping_address.{$field}" => "Field {$field} is required.",
                ]);
            }
        }

        $checkout->email = $data['email'];
        $checkout->shipping_address_json = $address;
        $checkout->billing_address_json = $data['billing_address'] ?? $address;
        $checkout->shipping_method_id = null;
        $checkout->status = CheckoutStatus::Addressed;
        $checkout->save();

        $this->pricing->calculate($checkout);

        return $checkout->refresh();
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->guardState($checkout, [
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
        ]);

        $cart = $checkout->cart()->with('lines.variant')->first();

        if (! $this->shipping->requiresShipping($cart)) {
            $checkout->shipping_method_id = null;
            $checkout->status = CheckoutStatus::ShippingSelected;
            $checkout->save();
            $this->pricing->calculate($checkout);

            return $checkout->refresh();
        }

        if ($shippingRateId === null) {
            throw ValidationException::withMessages(['shipping_method_id' => 'Shipping method required.']);
        }

        $available = $this->shipping->getAvailableRates(
            $checkout->store()->first(),
            $checkout->shipping_address_json ?? [],
            $cart,
        );

        $rate = $available->firstWhere('id', $shippingRateId);
        if (! $rate) {
            throw ValidationException::withMessages(['shipping_method_id' => 'Shipping method is not available for this address.']);
        }

        $checkout->shipping_method_id = $rate->id;
        $checkout->status = CheckoutStatus::ShippingSelected;
        $checkout->save();

        $this->pricing->calculate($checkout);

        return $checkout->refresh();
    }

    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $this->guardState($checkout, [
            CheckoutStatus::ShippingSelected,
            CheckoutStatus::PaymentPending,
        ]);

        if (! in_array($paymentMethod, ['credit_card', 'paypal', 'bank_transfer'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'Invalid payment method.']);
        }

        DB::transaction(function () use ($checkout, $paymentMethod): void {
            $checkout->payment_method = $paymentMethod;
            $checkout->status = CheckoutStatus::PaymentPending;
            $checkout->expires_at = now()->addHours(24);

            $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
            foreach ($cart->lines as $line) {
                $item = $line->variant?->inventoryItem;
                if ($item) {
                    $this->inventory->reserve($item, $line->quantity);
                }
            }

            $checkout->save();
        });

        return $checkout->refresh();
    }

    public function applyDiscount(Checkout $checkout, ?string $code): Checkout
    {
        $checkout->discount_code = $code;
        $checkout->save();

        $this->pricing->calculate($checkout);

        return $checkout->refresh();
    }

    public function expireCheckout(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::Completed || $checkout->status === CheckoutStatus::Expired) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            if ($checkout->status === CheckoutStatus::PaymentPending) {
                $cart = $checkout->cart()->with('lines.variant.inventoryItem')->first();
                foreach ($cart->lines as $line) {
                    $item = $line->variant?->inventoryItem;
                    if ($item) {
                        $this->inventory->release($item, $line->quantity);
                    }
                }
            }

            $checkout->status = CheckoutStatus::Expired;
            $checkout->save();
        });
    }

    public function markCompleted(Checkout $checkout): Checkout
    {
        $checkout->status = CheckoutStatus::Completed;
        $checkout->save();

        return $checkout;
    }

    public function totals(Checkout $checkout): PricingResult
    {
        return $this->pricing->calculate($checkout);
    }

    /**
     * @param  array<int, CheckoutStatus>  $allowed
     */
    protected function guardState(Checkout $checkout, array $allowed): void
    {
        if (! in_array($checkout->status, $allowed, true)) {
            throw new CheckoutStateException(
                "Checkout is in state {$checkout->status->value}; expected one of: "
                .implode(',', array_map(fn (CheckoutStatus $s) => $s->value, $allowed))
            );
        }
    }
}
