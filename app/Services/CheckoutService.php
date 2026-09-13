<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Enums\InventoryPolicy;
use App\Enums\PaymentMethod;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\UnserviceableAddressException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\ShippingRate;
use App\ValueObjects\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Checkout state machine per spec 05 section 6.
 */
class CheckoutService
{
    public function __construct(
        protected PricingEngine $pricing,
        protected ShippingCalculator $shipping,
        protected InventoryService $inventory,
        protected PaymentService $payments,
        protected OrderService $orders,
    ) {}

    public function createFromCart(Cart $cart, string $email, ?Customer $customer = null): Checkout
    {
        if ($cart->cartLines()->count() === 0) {
            throw ValidationException::withMessages([
                'cart_id' => ['The cart must contain at least one line item.'],
            ]);
        }

        return DB::transaction(function () use ($cart, $email, $customer): Checkout {
            $checkout = Checkout::query()->create([
                'store_id' => $cart->store_id,
                'cart_id' => $cart->getKey(),
                'customer_id' => $customer?->getKey() ?? $cart->customer_id,
                'status' => CheckoutStatus::Started,
                'email' => $email,
                'expires_at' => now()->addHours(24),
            ]);

            $this->pricing->calculate($checkout->refresh());

            return $checkout->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, mixed>|null  $billingAddress
     */
    public function setAddress(
        Checkout $checkout,
        array $shippingAddress,
        ?array $billingAddress = null,
        bool $useShippingAsBilling = true,
        ?string $email = null,
    ): Checkout {
        $this->assertStatus($checkout, [CheckoutStatus::Started, CheckoutStatus::Addressed]);

        return DB::transaction(function () use ($checkout, $shippingAddress, $billingAddress, $useShippingAsBilling, $email): Checkout {
            $shipping = Address::fromArray($shippingAddress)->toArray();

            if ($this->shipping->requiresShipping($checkout->cart)) {
                $zone = $this->shipping->getMatchingZone($checkout->store, $shipping);

                if ($zone === null) {
                    throw new UnserviceableAddressException('Cannot ship to this address.');
                }
            }

            $checkout->shipping_address_json = $shipping;
            $checkout->billing_address_json = $useShippingAsBilling || $billingAddress === null
                ? $shipping
                : Address::fromArray($billingAddress)->toArray();

            if ($email !== null) {
                $checkout->email = $email;
            }

            $checkout->status = CheckoutStatus::Addressed;
            $checkout->save();

            $this->pricing->calculate($checkout->refresh());

            event(new CheckoutAddressed($checkout->refresh()));

            return $checkout->refresh();
        });
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected]);

        return DB::transaction(function () use ($checkout, $shippingRateId): Checkout {
            if (! $this->shipping->requiresShipping($checkout->cart)) {
                $checkout->shipping_method_id = null;
                $checkout->status = CheckoutStatus::ShippingSelected;
                $checkout->save();

                $this->pricing->calculate($checkout->refresh());

                event(new CheckoutShippingSelected($checkout->refresh()));

                return $checkout->refresh();
            }

            if ($shippingRateId === null) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => ['The shipping method field is required.'],
                ]);
            }

            $rate = ShippingRate::query()->whereKey($shippingRateId)->first();

            if ($rate === null || ! $rate->is_active) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => ['The selected shipping method is invalid.'],
                ]);
            }

            $available = $this->shipping->getAvailableRates(
                $checkout->store,
                (array) ($checkout->shipping_address_json ?? []),
                $checkout->cart,
            );

            if (! $available->contains(fn ($option): bool => $option->id === $rate->getKey())) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => ['The selected shipping method is not available for this address.'],
                ]);
            }

            $checkout->shipping_method_id = $rate->getKey();
            $checkout->status = CheckoutStatus::ShippingSelected;
            $checkout->save();

            $this->pricing->calculate($checkout->refresh());

            event(new CheckoutShippingSelected($checkout->refresh()));

            return $checkout->refresh();
        });
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod $method): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::ShippingSelected]);

        return DB::transaction(function () use ($checkout, $method): Checkout {
            $checkout->payment_method = $method;
            $checkout->expires_at = now()->addHours(24);
            $checkout->status = CheckoutStatus::PaymentSelected;
            $checkout->save();

            $this->reserveInventory($checkout);

            return $checkout->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $paymentDetails
     */
    public function pay(Checkout $checkout, ?PaymentMethod $method = null, array $paymentDetails = []): Order
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            $existing = $this->orders->findByCheckout($checkout);

            if ($existing !== null) {
                return $existing;
            }

            throw new InvalidCheckoutTransitionException('This checkout has already been completed.');
        }

        if ($checkout->status === CheckoutStatus::ShippingSelected && $method !== null) {
            $checkout = $this->selectPaymentMethod($checkout, $method);
        }

        $this->assertStatus($checkout, [CheckoutStatus::PaymentSelected]);

        $effectiveMethod = $method ?? $checkout->payment_method;

        if ($effectiveMethod === null) {
            throw ValidationException::withMessages([
                'payment_method' => ['The payment method field is required.'],
            ]);
        }

        $checkout->payment_method = $effectiveMethod;
        $checkout->save();

        $this->pricing->calculate($checkout->refresh());

        $result = $this->payments->charge($checkout->refresh(), $effectiveMethod, $paymentDetails);

        if (! $result->success) {
            DB::transaction(function () use ($checkout): void {
                $this->releaseInventory($checkout->refresh());
                $checkout->status = CheckoutStatus::ShippingSelected;
                $checkout->save();
            });

            throw new PaymentFailedException($result->errorCode ?? 'payment_failed', $result->errorMessage ?? 'Payment failed.');
        }

        return $this->orders->createFromCheckout($checkout->refresh(), $result, $paymentDetails);
    }

    public function applyDiscount(Checkout $checkout, string $code, DiscountService $discounts): Checkout
    {
        $this->assertActive($checkout);

        return DB::transaction(function () use ($checkout, $code, $discounts): Checkout {
            $discount = $discounts->validateOrFail($code, $checkout->store, $checkout->cart);

            $checkout->discount_code = $discount->code;
            $checkout->save();

            $this->pricing->calculate($checkout->refresh());

            return $checkout->refresh();
        });
    }

    public function removeDiscount(Checkout $checkout): Checkout
    {
        $this->assertActive($checkout);

        return DB::transaction(function () use ($checkout): Checkout {
            $checkout->discount_code = null;
            $checkout->save();

            $this->pricing->calculate($checkout->refresh());

            return $checkout->refresh();
        });
    }

    public function expireCheckout(Checkout $checkout): Checkout
    {
        return DB::transaction(function () use ($checkout): Checkout {
            $fresh = $checkout->refresh();

            if (in_array($fresh->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
                return $fresh;
            }

            if ($fresh->status === CheckoutStatus::PaymentSelected) {
                $this->releaseInventory($fresh);
            }

            $fresh->status = CheckoutStatus::Expired;
            $fresh->save();

            event(new CheckoutExpired($fresh));

            return $fresh->refresh();
        });
    }

    public function reserveInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->cartLines()->with('variant')->get() as $line) {
            $item = $this->inventoryItemForVariant($checkout, $line->variant_id);

            if ($item !== null) {
                $this->inventory->reserve($item, $line->quantity);
            }
        }
    }

    public function releaseInventory(Checkout $checkout): void
    {
        foreach ($checkout->cart->cartLines()->get() as $line) {
            $item = $this->inventoryItemForVariant($checkout, $line->variant_id);

            if ($item !== null) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }

    protected function inventoryItemForVariant(Checkout $checkout, int $variantId): ?InventoryItem
    {
        return InventoryItem::query()
            ->where('store_id', $checkout->store_id)
            ->where('variant_id', $variantId)
            ->first()
            ?? InventoryItem::query()->create([
                'store_id' => $checkout->store_id,
                'variant_id' => $variantId,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'policy' => InventoryPolicy::Deny,
            ]);
    }

    /**
     * @param  array<int, CheckoutStatus>  $allowed
     */
    protected function assertStatus(Checkout $checkout, array $allowed): void
    {
        if ($checkout->status === CheckoutStatus::Expired) {
            throw new InvalidCheckoutTransitionException('This checkout has expired.');
        }

        if (! in_array($checkout->status, $allowed, true)) {
            throw new InvalidCheckoutTransitionException(
                'This action is not available for a checkout with status "'.$checkout->status->value.'".'
            );
        }
    }

    protected function assertActive(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
            throw new InvalidCheckoutTransitionException('This checkout is no longer active.');
        }
    }
}
