<?php

namespace App\Services;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Events\CheckoutAddressed;
use App\Events\CheckoutExpired;
use App\Events\CheckoutShippingSelected;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\TaxSettings;
use App\ValueObjects\Address;
use App\ValueObjects\DiscountValidationResult;
use App\ValueObjects\PricingResult;
use App\ValueObjects\ShippingRateVO;
use App\ValueObjects\TaxCalculationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Checkout state machine (spec 05 §6):
 * started -> addressed -> shipping_selected -> payment_selected -> completed.
 * Any active state can transition to expired. Pricing is recalculated on
 * every significant state change and snapshotted to checkouts.totals_json.
 */
class CheckoutService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private DiscountService $discounts,
        private ShippingCalculator $shipping,
        private TaxCalculator $taxCalculator,
        private InventoryService $inventory,
        private PaymentService $payments,
        private OrderService $orders,
    ) {}

    /**
     * Create a checkout from an active cart with at least one line.
     *
     * @throws ValidationException empty cart or inactive cart
     */
    public function createFromCart(Cart $cart, string $email, ?Customer $customer = null, ?string $discountCode = null): Checkout
    {
        $cart->loadMissing('lines');

        if ($cart->status !== CartStatus::Active) {
            throw ValidationException::withMessages([
                'cart_id' => ['The cart is not active.'],
            ]);
        }

        if ($cart->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'cart_id' => ['The cart is empty.'],
            ]);
        }

        $checkout = Checkout::create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $customer?->id ?? $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'email' => $email,
            'expires_at' => now()->addHours(24),
        ]);

        $this->recalculate($checkout);

        if ($discountCode !== null && $discountCode !== '') {
            $this->applyDiscount($checkout, $discountCode);
        }

        return $checkout->refresh();
    }

    /**
     * Transition started -> addressed: store email and addresses, verify the
     * address is serviceable, recalculate pricing (spec 05 §6.2).
     *
     * @param  array{email?: string, shipping_address: array<string, mixed>, billing_address?: array<string, mixed>|null, use_shipping_as_billing?: bool}  $data
     *
     * @throws InvalidCheckoutTransitionException|ValidationException
     */
    public function setAddress(Checkout $checkout, array $data): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Started, CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected], 'setAddress');

        $address = Address::fromArray($data['shipping_address']);

        if ($checkout->requiresShipping()
            && $this->shipping->getMatchingZone($checkout->store, $address) === null) {
            throw ValidationException::withMessages([
                'shipping_address' => ['Cannot ship to this address.'],
            ]);
        }

        $useShippingAsBilling = $data['use_shipping_as_billing'] ?? true;
        $billing = ! $useShippingAsBilling && ! empty($data['billing_address'])
            ? Address::fromArray($data['billing_address'])
            : $address;

        // Re-addressing after shipping was selected invalidates the chosen
        // rate (the new address may fall into a different zone).
        $checkout->fill([
            'email' => $data['email'] ?? $checkout->email,
            'shipping_address_json' => $address->toArray(),
            'billing_address_json' => $billing->toArray(),
            'shipping_method_id' => null,
            'status' => CheckoutStatus::Addressed,
        ])->save();

        $this->recalculate($checkout);

        CheckoutAddressed::dispatch($checkout);

        return $checkout->refresh();
    }

    /**
     * Transition addressed -> shipping_selected. Carts without shippable
     * lines skip the step: shipping_method_id stays null and shipping is 0
     * (spec 05 §6.2 / §9.3).
     *
     * @throws InvalidCheckoutTransitionException|ValidationException
     */
    public function setShippingMethod(Checkout $checkout, ?int $shippingRateId): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected], 'setShippingMethod');

        if (! $checkout->requiresShipping()) {
            $checkout->fill([
                'shipping_method_id' => null,
                'status' => CheckoutStatus::ShippingSelected,
            ])->save();

            $this->recalculate($checkout);

            CheckoutShippingSelected::dispatch($checkout);

            return $checkout->refresh();
        }

        $address = Address::fromArray($checkout->shipping_address_json ?? []);
        $available = $this->shipping->getAvailableRates($checkout->store, $address, $checkout->cart);

        if ($shippingRateId === null || ! $available->contains('id', $shippingRateId)) {
            throw ValidationException::withMessages([
                'shipping_method_id' => ['The selected shipping method is not available for this address.'],
            ]);
        }

        $checkout->fill([
            'shipping_method_id' => $shippingRateId,
            'status' => CheckoutStatus::ShippingSelected,
        ])->save();

        $this->recalculate($checkout);

        CheckoutShippingSelected::dispatch($checkout);

        return $checkout->refresh();
    }

    /**
     * Transition shipping_selected -> payment_selected: store the method,
     * reserve inventory for all lines and set the 24h expiry (spec 05 §6.2).
     *
     * @throws InvalidCheckoutTransitionException
     */
    public function selectPaymentMethod(Checkout $checkout, PaymentMethod|string $method): Checkout
    {
        $this->assertStatus($checkout, [CheckoutStatus::ShippingSelected], 'selectPaymentMethod');

        $method = $method instanceof PaymentMethod ? $method : PaymentMethod::from($method);

        DB::transaction(function () use ($checkout, $method): void {
            $checkout->loadMissing('cart.lines.variant.inventoryItem');

            foreach ($checkout->cart->lines as $line) {
                $item = $line->variant?->inventoryItem;

                if ($item !== null) {
                    $this->inventory->reserve($item, $line->quantity);
                }
            }

            $checkout->fill([
                'payment_method' => $method,
                'expires_at' => now()->addHours(24),
                'status' => CheckoutStatus::PaymentSelected,
            ])->save();
        });

        return $checkout->refresh();
    }

    /**
     * Transition payment_selected -> completed (spec 05 §6.2). Charges the
     * payment via the Mock PSP and creates the order. IDEMPOTENT: repeated
     * calls for the same checkout return the already-created order.
     *
     * On payment failure the reserved inventory is released (in its own
     * transaction so the release is not rolled back by the thrown
     * exception), the checkout stays payment_selected, and a
     * PaymentFailedException is thrown. The reservation is refreshed
     * (released + re-reserved) before every charge attempt so retries after
     * a decline re-establish it and re-validate availability.
     *
     * @param  array<string, mixed>  $paymentDetails
     *
     * @throws InvalidCheckoutTransitionException|PaymentFailedException|InsufficientInventoryException
     */
    public function completeCheckout(Checkout $checkout, array $paymentDetails = []): Order
    {
        $this->assertStatus($checkout, [CheckoutStatus::PaymentSelected, CheckoutStatus::Completed], 'completeCheckout');

        $existing = Order::query()->where('checkout_id', $checkout->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $checkout->loadMissing('cart.lines.variant.inventoryItem');

        DB::transaction(function () use ($checkout): void {
            foreach ($checkout->cart->lines as $line) {
                $item = $line->variant?->inventoryItem;

                if ($item !== null) {
                    $this->inventory->release($item, $line->quantity);
                    $this->inventory->reserve($item, $line->quantity);
                }
            }
        });

        $result = $this->payments->charge($checkout, $checkout->payment_method, $paymentDetails);

        if (! $result->success) {
            DB::transaction(function () use ($checkout): void {
                foreach ($checkout->cart->lines as $line) {
                    $item = $line->variant?->inventoryItem;

                    if ($item !== null) {
                        $this->inventory->release($item, $line->quantity);
                    }
                }
            });

            throw new PaymentFailedException(
                $result->errorCode ?? 'payment_failed',
                $result->errorMessage ?? 'The payment failed.',
            );
        }

        return $this->orders->createFromCheckout($checkout, $result, $paymentDetails);
    }

    /**
     * Validate and apply a discount code, then recalculate totals.
     */
    public function applyDiscount(Checkout $checkout, string $code): DiscountValidationResult
    {
        $result = $this->discounts->validate($code, $checkout->store, $checkout->cart);

        if ($result->valid) {
            $checkout->fill(['discount_code' => $result->discount->code])->save();
            $this->recalculate($checkout);
        }

        return $result;
    }

    /**
     * Remove the applied discount code and recalculate totals.
     */
    public function removeDiscount(Checkout $checkout): void
    {
        $checkout->fill(['discount_code' => null])->save();
        $this->recalculate($checkout);
    }

    /**
     * Transition any active state -> expired, releasing reserved inventory
     * when payment had been selected (spec 05 §6.2).
     */
    public function expireCheckout(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed, CheckoutStatus::Expired], true)) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            if ($checkout->status === CheckoutStatus::PaymentSelected) {
                $checkout->loadMissing('cart.lines.variant.inventoryItem');

                foreach ($checkout->cart->lines as $line) {
                    $item = $line->variant?->inventoryItem;

                    if ($item !== null) {
                        $this->inventory->release($item, $line->quantity);
                    }
                }
            }

            $checkout->fill(['status' => CheckoutStatus::Expired])->save();
        });

        CheckoutExpired::dispatch($checkout);
    }

    /**
     * Recalculate pricing and persist the snapshot: checkouts.totals_json,
     * per-line discount amounts on cart_lines, and the tax provider snapshot
     * (spec 05 §5.4 / §6.2).
     */
    public function recalculate(Checkout $checkout): PricingResult
    {
        $cart = $checkout->cart()->with(['lines.variant.product.collections'])->firstOrFail();
        $cart->loadMissing('lines.variant.product.collections');

        $lines = $cart->lines->values()->map(fn ($line): array => [
            'variant_id' => $line->variant_id,
            'product_id' => $line->variant?->product_id,
            'collection_ids' => $line->variant?->product?->collections->pluck('id')->all() ?? [],
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'requires_shipping' => (bool) ($line->variant?->requires_shipping ?? false),
        ])->all();

        $store = $checkout->store;

        $codeDiscount = $checkout->discount_code !== null
            ? Discount::query()
                ->where('store_id', $store->id)
                ->whereRaw('lower(code) = ?', [mb_strtolower($checkout->discount_code)])
                ->first()
            : null;

        $automaticDiscounts = $this->discounts->getApplicableAutomaticDiscounts($store, $cart)->all();

        $shippingRate = $this->resolveShippingRate($checkout, $cart);

        $taxSettings = TaxSettings::find($store->id) ?? new TaxSettings([
            'store_id' => $store->id,
            'prices_include_tax' => false,
            'config_json' => [],
        ]);

        $address = ! empty($checkout->shipping_address_json)
            ? Address::fromArray($checkout->shipping_address_json)
            : null;

        $result = $this->pricingEngine->calculate(
            lines: $lines,
            codeDiscount: $codeDiscount,
            automaticDiscounts: $automaticDiscounts,
            shippingRate: $shippingRate,
            taxSettings: $taxSettings,
            address: $address,
            currency: $cart->currency,
        );

        // Persist per-line discount allocations on the cart lines.
        foreach ($cart->lines->values() as $index => $line) {
            $discountAmount = $result->lineDiscounts[$index] ?? 0;

            if ($line->line_discount_amount !== $discountAmount) {
                $line->line_discount_amount = $discountAmount;
                $line->recalculate();
            }
        }

        $checkout->fill([
            'totals_json' => $result->toArray(),
            'tax_provider_snapshot_json' => $address !== null
                ? $this->buildTaxSnapshot($lines, $result, $shippingRate, $taxSettings, $address)
                : null,
        ])->save();

        return $result;
    }

    /**
     * Resolve the selected shipping rate into a calculated VO, if any.
     */
    private function resolveShippingRate(Checkout $checkout, Cart $cart): ?ShippingRateVO
    {
        if ($checkout->shipping_method_id === null || ! $checkout->requiresShipping()) {
            return null;
        }

        $rate = ShippingRate::find($checkout->shipping_method_id);

        if ($rate === null) {
            return null;
        }

        $amount = $this->shipping->calculate($rate, $cart);

        if ($amount === null) {
            return null;
        }

        $config = $rate->config_json ?? [];

        return new ShippingRateVO(
            id: $rate->id,
            name: $rate->name,
            amount: $amount,
            type: $rate->type,
            estimatedDaysMin: isset($config['estimated_days_min']) ? (int) $config['estimated_days_min'] : null,
            estimatedDaysMax: isset($config['estimated_days_max']) ? (int) $config['estimated_days_max'] : null,
        );
    }

    /**
     * Build the tax_provider_snapshot_json payload (spec 02 §2.2).
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function buildTaxSnapshot(array $lines, PricingResult $result, ?ShippingRateVO $shippingRate, TaxSettings $taxSettings, Address $address): array
    {
        $lineItems = [];

        foreach ($lines as $index => $line) {
            $lineItems[] = [
                'variant_id' => $line['variant_id'] ?? null,
                'amount' => ($line['unit_price_amount'] * $line['quantity']) - ($result->lineDiscounts[$index] ?? 0),
            ];
        }

        $taxResult = $this->taxCalculator->calculate(new TaxCalculationRequest(
            lineItems: $lineItems,
            shippingAmount: $result->shipping,
            address: $address,
            taxSettings: $taxSettings,
        ));

        return [
            'provider' => $taxSettings->mode?->value === 'provider' ? $taxSettings->provider : 'manual',
            'calculated_at' => now()->toIso8601ZuluString(),
            'lines' => $taxResult->lineDetails,
            'shipping_tax_amount' => $taxResult->shippingTaxAmount,
            'shipping_tax_rate' => $taxResult->shippingTaxRate,
        ];
    }

    /**
     * Guard the checkout's current status against the allowed set.
     *
     * @param  array<int, CheckoutStatus>  $allowed
     *
     * @throws InvalidCheckoutTransitionException
     */
    private function assertStatus(Checkout $checkout, array $allowed, string $transition): void
    {
        if (! in_array($checkout->status, $allowed, true)) {
            throw InvalidCheckoutTransitionException::make($checkout->status->value, $transition);
        }
    }
}
