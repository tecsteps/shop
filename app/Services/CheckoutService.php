<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutStateException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\ValueObjects\PricingResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly ShippingCalculator $shippingCalculator,
        private readonly DiscountService $discountService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function createFromCart(Cart $cart, ?string $email = null): Checkout
    {
        /** @var Checkout $checkout */
        $checkout = DB::transaction(function () use ($cart, $email): Checkout {
            $lockedCart = $this->lockCart($cart);
            $lockedCart->loadMissing('lines');

            if ($lockedCart->status !== CartStatus::Active) {
                throw InvalidCheckoutStateException::cartNotActive((int) $lockedCart->id, $lockedCart->status->value);
            }

            if ($lockedCart->lines->isEmpty()) {
                throw InvalidCheckoutStateException::emptyCart((int) $lockedCart->id);
            }

            $checkout = Checkout::query()->create([
                'store_id' => (int) $lockedCart->store_id,
                'cart_id' => (int) $lockedCart->id,
                'customer_id' => $lockedCart->customer_id,
                'status' => CheckoutStatus::Started,
                'email' => $email,
                'expires_at' => now()->addDay(),
            ]);

            $this->pricingEngine->calculate($checkout);

            return $checkout->refresh();
        });

        return $checkout;
    }

    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, mixed>|null  $billingAddress
     */
    public function setAddress(
        Checkout $checkout,
        string $email,
        array $shippingAddress,
        ?array $billingAddress = null,
        bool $useShippingAsBilling = true,
    ): Checkout {
        /** @var Checkout $updated */
        $updated = DB::transaction(function () use ($checkout, $email, $shippingAddress, $billingAddress, $useShippingAsBilling): Checkout {
            $lockedCheckout = $this->lockCheckout($checkout);
            $this->assertMutable($lockedCheckout);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('A valid email address is required.');
            }

            $requiresShipping = $this->shippingCalculator->cartRequiresShipping($lockedCheckout->cart);

            $normalizedShipping = [];

            if ($requiresShipping) {
                $normalizedShipping = $this->normalizeAddress($shippingAddress, (int) $lockedCheckout->id);

                if ($this->shippingCalculator->getAvailableRates($lockedCheckout->store, $normalizedShipping, $lockedCheckout->cart)->isEmpty()) {
                    throw InvalidCheckoutStateException::unserviceableAddress((int) $lockedCheckout->id);
                }
            }

            $normalizedBilling = null;

            if ($useShippingAsBilling) {
                $normalizedBilling = $normalizedShipping;
            } elseif ($billingAddress !== null) {
                $normalizedBilling = $this->normalizeAddress($billingAddress, (int) $lockedCheckout->id, requireAllFields: false);
            }

            $lockedCheckout->email = $email;
            $lockedCheckout->shipping_address_json = $normalizedShipping === [] ? null : $normalizedShipping;
            $lockedCheckout->billing_address_json = $normalizedBilling === [] ? null : $normalizedBilling;
            $lockedCheckout->status = CheckoutStatus::Addressed;
            $lockedCheckout->save();

            $this->pricingEngine->calculate($lockedCheckout);

            return $lockedCheckout->refresh();
        });

        return $updated;
    }

    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        /** @var Checkout $updated */
        $updated = DB::transaction(function () use ($checkout, $shippingRateId): Checkout {
            $lockedCheckout = $this->lockCheckout($checkout);
            $this->assertState($lockedCheckout, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected]);
            $this->assertMutable($lockedCheckout);

            if (! $this->shippingCalculator->cartRequiresShipping($lockedCheckout->cart)) {
                $lockedCheckout->shipping_method_id = null;
                $lockedCheckout->status = CheckoutStatus::ShippingSelected;
                $lockedCheckout->save();

                $this->pricingEngine->calculate($lockedCheckout);

                return $lockedCheckout->refresh();
            }

            $address = $lockedCheckout->shipping_address_json;

            if (! is_array($address)) {
                throw InvalidCheckoutStateException::shippingAddressRequired((int) $lockedCheckout->id);
            }

            if ($shippingRateId === null || $shippingRateId <= 0) {
                throw InvalidCheckoutStateException::invalidShippingMethod((int) $lockedCheckout->id, 0);
            }

            $quote = $this->shippingCalculator->selectActiveRateByZone(
                store: $lockedCheckout->store,
                address: $address,
                cart: $lockedCheckout->cart,
                shippingRateId: $shippingRateId,
                checkoutId: (int) $lockedCheckout->id,
            );

            $lockedCheckout->shipping_method_id = $quote->rateId;
            $lockedCheckout->status = CheckoutStatus::ShippingSelected;
            $lockedCheckout->save();

            $this->pricingEngine->calculate($lockedCheckout);

            return $lockedCheckout->refresh();
        });

        return $updated;
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod|string $paymentMethod): Checkout
    {
        /** @var Checkout $updated */
        $updated = DB::transaction(function () use ($checkout, $paymentMethod): Checkout {
            $lockedCheckout = $this->lockCheckout($checkout);
            $this->assertMutable($lockedCheckout);

            $resolvedPaymentMethod = $this->resolvePaymentMethod($lockedCheckout, $paymentMethod);

            if ($lockedCheckout->status === CheckoutStatus::PaymentSelected
                && $lockedCheckout->payment_method === $resolvedPaymentMethod) {
                return $lockedCheckout->refresh();
            }

            $this->assertState($lockedCheckout, [CheckoutStatus::ShippingSelected]);

            /** @var CartLine $line */
            foreach ($lockedCheckout->cart->lines as $line) {
                $inventoryItem = $line->variant?->inventoryItem;

                if ($inventoryItem === null) {
                    continue;
                }

                $this->inventoryService->reserve($inventoryItem, (int) $line->quantity);
            }

            $lockedCheckout->payment_method = $resolvedPaymentMethod;
            $lockedCheckout->expires_at = now()->addDay();
            $lockedCheckout->status = CheckoutStatus::PaymentSelected;
            $lockedCheckout->save();

            $this->pricingEngine->calculate($lockedCheckout);

            return $lockedCheckout->refresh();
        });

        return $updated;
    }

    public function applyDiscount(Checkout $checkout, string $code): Checkout
    {
        /** @var Checkout $updated */
        $updated = DB::transaction(function () use ($checkout, $code): Checkout {
            $lockedCheckout = $this->lockCheckout($checkout);
            $this->assertMutable($lockedCheckout);

            $discount = $this->discountService->validate($code, $lockedCheckout->store, $lockedCheckout->cart);

            $lockedCheckout->discount_code = (string) $discount->code;
            $lockedCheckout->save();

            $this->pricingEngine->calculate($lockedCheckout);

            return $lockedCheckout->refresh();
        });

        return $updated;
    }

    public function removeDiscount(Checkout $checkout): Checkout
    {
        /** @var Checkout $updated */
        $updated = DB::transaction(function () use ($checkout): Checkout {
            $lockedCheckout = $this->lockCheckout($checkout);
            $this->assertMutable($lockedCheckout);

            $lockedCheckout->discount_code = null;
            $lockedCheckout->save();

            $this->pricingEngine->calculate($lockedCheckout);

            return $lockedCheckout->refresh();
        });

        return $updated;
    }

    public function computeTotals(Checkout $checkout): PricingResult
    {
        return $this->pricingEngine->calculate($checkout);
    }

    /**
     * @return Collection<int, \App\ValueObjects\ShippingRateQuote>
     */
    public function availableShippingMethods(Checkout $checkout): Collection
    {
        $checkout->loadMissing(['store', 'cart.lines.variant']);

        if (! is_array($checkout->shipping_address_json)) {
            return collect();
        }

        return $this->shippingCalculator->getAvailableRates(
            store: $checkout->store,
            address: $checkout->shipping_address_json,
            cart: $checkout->cart,
        );
    }

    private function assertMutable(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
            throw InvalidCheckoutStateException::immutable($checkout);
        }
    }

    /**
     * @param  list<CheckoutStatus>  $expectedStates
     */
    private function assertState(Checkout $checkout, array $expectedStates): void
    {
        if (in_array($checkout->status, $expectedStates, true)) {
            return;
        }

        throw InvalidCheckoutStateException::invalidTransition(
            checkout: $checkout,
            expectedStates: array_map(static fn (CheckoutStatus $status): string => $status->value, $expectedStates),
        );
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, string>
     */
    private function normalizeAddress(array $address, int $checkoutId, bool $requireAllFields = true): array
    {
        $requiredFields = [
            'first_name',
            'last_name',
            'address1',
            'city',
            'country',
            'country_code',
            'postal_code',
        ];

        if ($requireAllFields) {
            foreach ($requiredFields as $field) {
                $value = $address[$field] ?? null;

                if (! is_string($value) || trim($value) === '') {
                    throw InvalidCheckoutStateException::missingAddressField($checkoutId, $field);
                }
            }
        }

        $normalized = [];

        foreach ($address as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }

            $normalized[$key] = trim((string) $value);
        }

        if (isset($normalized['country_code'])) {
            $normalized['country_code'] = strtoupper($normalized['country_code']);
        }

        if (isset($normalized['province_code'])) {
            $normalized['province_code'] = strtoupper($normalized['province_code']);
        }

        return $normalized;
    }

    private function resolvePaymentMethod(Checkout $checkout, PaymentMethod|string $paymentMethod): PaymentMethod
    {
        if ($paymentMethod instanceof PaymentMethod) {
            return $paymentMethod;
        }

        $resolved = PaymentMethod::tryFrom($paymentMethod);

        if ($resolved === null) {
            throw InvalidCheckoutStateException::invalidPaymentMethod((int) $checkout->id, $paymentMethod);
        }

        return $resolved;
    }

    private function lockCart(Cart $cart): Cart
    {
        /** @var Cart $locked */
        $locked = Cart::query()
            ->whereKey($cart->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }

    private function lockCheckout(Checkout $checkout): Checkout
    {
        /** @var Checkout $locked */
        $locked = Checkout::query()
            ->with(['store', 'cart.lines.variant.inventoryItem', 'cart.lines.variant.product.collections'])
            ->whereKey($checkout->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }
}
