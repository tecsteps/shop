<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidShippingRateException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\ShippingRate;
use App\ValueObjects\PricingResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

class CheckoutService
{
    /**
     * Checkouts expire 24 hours after payment selection (spec 05 section 6.2).
     */
    public const int EXPIRY_HOURS = 24;

    public function __construct(
        protected PricingEngine $pricingEngine,
        protected ShippingCalculator $shippingCalculator,
        protected InventoryService $inventoryService,
    ) {}

    /**
     * Start a checkout from a non-empty cart.
     *
     * @throws ValidationException
     */
    public function createFromCart(Cart $cart, ?Customer $customer = null, ?string $discountCode = null): Checkout
    {
        if (! $cart->lines()->exists()) {
            throw ValidationException::withMessages([
                'cart' => __('Cannot start a checkout for an empty cart.'),
            ]);
        }

        $checkout = Checkout::query()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->getKey(),
            'customer_id' => $customer?->getKey() ?? $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'email' => $customer?->email,
            'discount_code' => $discountCode,
        ]);

        $this->pricingEngine->calculate($checkout);

        return $checkout->refresh();
    }

    /**
     * Transition started -> addressed. Re-addressing resets any selected
     * shipping method and recalculates pricing (zones and tax may change).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws InvalidCheckoutTransitionException
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertStatusIn($checkout, [
            CheckoutStatus::Started,
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
        ], 'set the address on');

        $validated = Validator::make($data, [
            'email' => ['required', 'email'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.address1' => ['required', 'string', 'max:255'],
            'shipping_address.address2' => ['nullable', 'string', 'max:255'],
            'shipping_address.company' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.province' => ['nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:32'],
            'shipping_address.country_code' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string', 'max:32'],
            'shipping_address.phone' => ['nullable', 'string', 'max:64'],
            'billing_address' => ['nullable', 'array'],
        ])->validate();

        $checkout->forceFill([
            'email' => $validated['email'],
            'shipping_address_json' => $validated['shipping_address'],
            'billing_address_json' => $validated['billing_address'] ?? $validated['shipping_address'],
            'shipping_method_id' => null,
            'status' => CheckoutStatus::Addressed,
        ])->save();

        $this->pricingEngine->calculate($checkout);

        return $checkout->refresh();
    }

    /**
     * Transition addressed -> shipping_selected. When nothing in the cart
     * requires shipping the step is skipped with a null rate and zero cost.
     *
     * @throws InvalidShippingRateException
     * @throws InvalidCheckoutTransitionException
     */
    public function setShippingMethod(Checkout $checkout, ?int $rateId = null): Checkout
    {
        $this->assertStatusIn($checkout, [
            CheckoutStatus::Addressed,
            CheckoutStatus::ShippingSelected,
        ], 'select a shipping method for');

        $cart = Cart::query()->withoutGlobalScopes()->findOrFail($checkout->cart_id);

        if (! $cart->requiresShipping()) {
            $checkout->forceFill([
                'shipping_method_id' => null,
                'status' => CheckoutStatus::ShippingSelected,
            ])->save();

            $this->pricingEngine->calculate($checkout);

            return $checkout->refresh();
        }

        $rate = $rateId === null ? null : ShippingRate::query()->find($rateId);

        if ($rate === null || ! $this->shippingCalculator->rateMatchesAddress(
            $rate,
            $checkout->store,
            $checkout->shipping_address_json ?? [],
        )) {
            throw InvalidShippingRateException::notApplicable((int) $rateId);
        }

        $checkout->forceFill([
            'shipping_method_id' => $rate->getKey(),
            'status' => CheckoutStatus::ShippingSelected,
        ])->save();

        $this->pricingEngine->calculate($checkout);

        return $checkout->refresh();
    }

    /**
     * Transition shipping_selected -> payment_selected: stores the payment
     * method, reserves inventory, and starts the 24 hour expiry clock.
     *
     * @throws ValidationException
     * @throws InsufficientInventoryException
     * @throws InvalidCheckoutTransitionException
     */
    public function selectPaymentMethod(Checkout $checkout, string $paymentMethod): Checkout
    {
        $this->assertStatusIn($checkout, [CheckoutStatus::ShippingSelected], 'select a payment method for');

        if (! in_array($paymentMethod, ['credit_card', 'paypal', 'bank_transfer'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => __('Unsupported payment method.'),
            ]);
        }

        return DB::transaction(function () use ($checkout, $paymentMethod): Checkout {
            $this->eachLineWithInventory($checkout, function (CartLine $line): void {
                $this->inventoryService->reserve($line->variant->inventoryItem, $line->quantity);
            });

            $checkout->forceFill([
                'payment_method' => $paymentMethod,
                'expires_at' => now()->addHours(self::EXPIRY_HOURS),
                'status' => CheckoutStatus::PaymentSelected,
            ])->save();

            return $checkout->refresh();
        });
    }

    /**
     * Transition payment_selected -> completed: charges the mock PSP and
     * creates the order. Must be idempotent.
     *
     * @param  array<string, mixed>  $paymentMethodData
     *
     * @throws InvalidCheckoutTransitionException
     */
    public function completeCheckout(Checkout $checkout, array $paymentMethodData = []): never
    {
        $this->assertStatusIn($checkout, [CheckoutStatus::PaymentSelected], 'complete');

        /*
         * Phase 5 integration point: charge via MockPaymentProvider, create
         * the order (sequential number, line snapshots, payment record),
         * commit or keep reserved inventory by payment method, increment the
         * discount usage_count, mark the cart converted and the checkout
         * completed, and dispatch OrderCreated. Idempotency: return the
         * existing order when one was already created for this checkout.
         */
        throw new LogicException('Phase 5: order creation is not implemented yet.');
    }

    /**
     * Transition any active state -> expired, releasing inventory that was
     * reserved at payment selection.
     */
    public function expireCheckout(Checkout $checkout): void
    {
        if (! $checkout->status->isActive()) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $this->eachLineWithInventory($checkout, function (CartLine $line): void {
                    $this->inventoryService->release($line->variant->inventoryItem, $line->quantity);
                });
            }

            $checkout->forceFill(['status' => CheckoutStatus::Expired])->save();
        });
    }

    /**
     * Recalculate and snapshot the checkout totals.
     */
    public function recalculate(Checkout $checkout): PricingResult
    {
        return $this->pricingEngine->calculate($checkout);
    }

    /**
     * Run a callback for every cart line whose variant tracks inventory.
     *
     * @param  callable(CartLine): void  $callback
     */
    protected function eachLineWithInventory(Checkout $checkout, callable $callback): void
    {
        $lines = CartLine::query()
            ->where('cart_id', $checkout->cart_id)
            ->with('variant.inventoryItem')
            ->get();

        foreach ($lines as $line) {
            if ($line->variant?->inventoryItem !== null) {
                $callback($line);
            }
        }
    }

    /**
     * @param  list<CheckoutStatus>  $allowed
     *
     * @throws InvalidCheckoutTransitionException
     */
    protected function assertStatusIn(Checkout $checkout, array $allowed, string $action): void
    {
        if (! in_array($checkout->status, $allowed, true)) {
            throw InvalidCheckoutTransitionException::fromStatus($checkout->status, $action);
        }
    }
}
