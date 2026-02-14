<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Services\CheckoutService;
use App\ValueObjects\PricingResult;
use App\ValueObjects\ShippingRateQuote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class CheckoutController extends StorefrontController
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function show(Request $request, int $checkoutId): View
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);
        $this->isExpiredCheckout($checkout);

        $checkout->loadMissing(['cart.lines.variant.product', 'customer']);

        $totals = $this->normalizeTotals($checkout);
        $status = $this->checkoutStatus($checkout);
        $shippingMethods = $this->availableShippingMethods($checkout);
        $discount = $this->currentDiscount($checkout);
        $requiresShipping = $this->cartRequiresShipping($checkout);

        return view('storefront.checkout.show', [
            'checkout' => $checkout,
            'totals' => $totals,
            'status' => $status,
            'shippingMethods' => $shippingMethods,
            'requiresShipping' => $requiresShipping,
            'paymentMethods' => [
                PaymentMethod::CreditCard->value => 'Credit card',
                PaymentMethod::Paypal->value => 'PayPal',
                PaymentMethod::BankTransfer->value => 'Bank transfer',
            ],
            'appliedDiscount' => $discount,
        ]);
    }

    public function updateAddress(Request $request, int $checkoutId): RedirectResponse
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);

        if ($this->isExpiredCheckout($checkout)) {
            return $this->redirectToCheckoutWithErrors($checkoutId, ['checkout' => 'Checkout expired.']);
        }

        try {
            $validated = Validator::make($request->all(), [
                'email' => ['required', 'email', 'max:255'],
                'shipping_address' => ['nullable', 'array'],
                'shipping_address.first_name' => ['required_with:shipping_address', 'string', 'max:255'],
                'shipping_address.last_name' => ['required_with:shipping_address', 'string', 'max:255'],
                'shipping_address.address1' => ['required_with:shipping_address', 'string', 'max:500'],
                'shipping_address.address2' => ['nullable', 'string', 'max:500'],
                'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:255'],
                'shipping_address.province' => ['nullable', 'string', 'max:255'],
                'shipping_address.province_code' => ['nullable', 'string', 'max:10'],
                'shipping_address.country' => ['required_with:shipping_address', 'string', 'max:255'],
                'shipping_address.country_code' => ['required_with:shipping_address', 'string', 'size:2'],
                'shipping_address.postal_code' => ['required_with:shipping_address', 'string', 'max:20'],
                'shipping_address.phone' => ['nullable', 'string', 'max:50'],
                'billing_address' => ['nullable', 'array'],
                'billing_address.first_name' => ['required_with:billing_address', 'string', 'max:255'],
                'billing_address.last_name' => ['required_with:billing_address', 'string', 'max:255'],
                'billing_address.address1' => ['required_with:billing_address', 'string', 'max:500'],
                'billing_address.address2' => ['nullable', 'string', 'max:500'],
                'billing_address.city' => ['required_with:billing_address', 'string', 'max:255'],
                'billing_address.province' => ['nullable', 'string', 'max:255'],
                'billing_address.province_code' => ['nullable', 'string', 'max:10'],
                'billing_address.country' => ['required_with:billing_address', 'string', 'max:255'],
                'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2'],
                'billing_address.postal_code' => ['required_with:billing_address', 'string', 'max:20'],
                'billing_address.phone' => ['nullable', 'string', 'max:50'],
                'use_shipping_as_billing' => ['nullable', 'boolean'],
            ])->validate();
        } catch (ValidationException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $exception->errors(), withInput: true);
        }

        $shippingAddress = is_array($validated['shipping_address'] ?? null) ? $validated['shipping_address'] : [];
        $useShippingAsBilling = (bool) ($validated['use_shipping_as_billing'] ?? true);
        $billingAddress = $useShippingAsBilling
            ? null
            : (is_array($validated['billing_address'] ?? null) ? $validated['billing_address'] : null);

        try {
            $this->checkoutService->setAddress(
                checkout: $checkout,
                email: (string) $validated['email'],
                shippingAddress: $shippingAddress,
                billingAddress: $billingAddress,
                useShippingAsBilling: $useShippingAsBilling,
            );
        } catch (InvalidCheckoutStateException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $this->checkoutStateErrors($exception), withInput: true);
        } catch (InvalidArgumentException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'email' => $exception->getMessage(),
            ], withInput: true);
        } catch (Throwable) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'checkout' => 'Unable to update the address right now.',
            ], withInput: true);
        }

        return redirect()->route('storefront.checkout.show', ['checkoutId' => $checkoutId])
            ->with('status', 'Address saved.');
    }

    public function selectShippingMethod(Request $request, int $checkoutId): RedirectResponse
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);

        if ($this->isExpiredCheckout($checkout)) {
            return $this->redirectToCheckoutWithErrors($checkoutId, ['checkout' => 'Checkout expired.']);
        }

        try {
            $validated = Validator::make($request->all(), [
                'shipping_method_id' => ['nullable', 'integer', 'min:1'],
            ])->validate();
        } catch (ValidationException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $exception->errors(), withInput: true);
        }

        $shippingMethodId = isset($validated['shipping_method_id']) && is_numeric($validated['shipping_method_id'])
            ? (int) $validated['shipping_method_id']
            : null;

        if ($this->cartRequiresShipping($checkout) && $shippingMethodId === null) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'shipping_method_id' => 'Please select a shipping method.',
            ], withInput: true);
        }

        try {
            $this->checkoutService->setShippingMethod($checkout, $shippingMethodId);
        } catch (InvalidCheckoutStateException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $this->checkoutStateErrors($exception), withInput: true);
        } catch (Throwable) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'checkout' => 'Unable to select a shipping method right now.',
            ], withInput: true);
        }

        return redirect()->route('storefront.checkout.show', ['checkoutId' => $checkoutId])
            ->with('status', 'Shipping method selected.');
    }

    public function selectPaymentMethod(Request $request, int $checkoutId): RedirectResponse
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);

        if ($this->isExpiredCheckout($checkout)) {
            return $this->redirectToCheckoutWithErrors($checkoutId, ['checkout' => 'Checkout expired.']);
        }

        try {
            $validated = Validator::make($request->all(), [
                'payment_method' => ['required', 'string', Rule::in([
                    PaymentMethod::CreditCard->value,
                    PaymentMethod::Paypal->value,
                    PaymentMethod::BankTransfer->value,
                ])],
            ])->validate();
        } catch (ValidationException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $exception->errors(), withInput: true);
        }

        try {
            $this->checkoutService->selectPaymentMethod($checkout, (string) $validated['payment_method']);
        } catch (InvalidCheckoutStateException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $this->checkoutStateErrors($exception), withInput: true);
        } catch (Throwable) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'checkout' => 'Unable to select a payment method right now.',
            ], withInput: true);
        }

        return redirect()->route('storefront.checkout.show', ['checkoutId' => $checkoutId])
            ->with('status', 'Payment method selected.');
    }

    public function applyDiscount(Request $request, int $checkoutId): RedirectResponse
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);

        if ($this->isExpiredCheckout($checkout)) {
            return $this->redirectToCheckoutWithErrors($checkoutId, ['checkout' => 'Checkout expired.']);
        }

        try {
            $validated = Validator::make($request->all(), [
                'code' => ['required', 'string', 'max:50'],
            ])->validate();
        } catch (ValidationException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $exception->errors(), withInput: true);
        }

        try {
            $this->checkoutService->applyDiscount($checkout, (string) $validated['code']);
        } catch (InvalidDiscountException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'code' => $exception->getMessage(),
            ], withInput: true);
        } catch (InvalidCheckoutStateException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $this->checkoutStateErrors($exception), withInput: true);
        } catch (Throwable) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'checkout' => 'Unable to apply discount right now.',
            ], withInput: true);
        }

        return redirect()->route('storefront.checkout.show', ['checkoutId' => $checkoutId])
            ->with('status', 'Discount applied.');
    }

    public function removeDiscount(Request $request, int $checkoutId): RedirectResponse
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);

        if ($this->isExpiredCheckout($checkout)) {
            return $this->redirectToCheckoutWithErrors($checkoutId, ['checkout' => 'Checkout expired.']);
        }

        try {
            $this->checkoutService->removeDiscount($checkout);
        } catch (InvalidCheckoutStateException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $this->checkoutStateErrors($exception));
        } catch (Throwable) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'checkout' => 'Unable to remove discount right now.',
            ]);
        }

        return redirect()->route('storefront.checkout.show', ['checkoutId' => $checkoutId])
            ->with('status', 'Discount removed.');
    }

    public function pay(Request $request, int $checkoutId): RedirectResponse
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);

        if ($this->isExpiredCheckout($checkout)) {
            return $this->redirectToCheckoutWithErrors($checkoutId, ['checkout' => 'Checkout expired.']);
        }

        $status = $this->checkoutStatus($checkout);

        if ($status === CheckoutStatus::Completed->value) {
            return redirect()->route('storefront.checkout.confirmation', ['checkoutId' => $checkoutId])
                ->with('status', 'Checkout already completed.');
        }

        if ($status !== CheckoutStatus::PaymentSelected->value) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'checkout' => 'Checkout is not ready for payment.',
            ]);
        }

        try {
            $validated = Validator::make($request->all(), [
                'payment_method' => ['nullable', 'string', Rule::in([
                    PaymentMethod::CreditCard->value,
                    PaymentMethod::Paypal->value,
                    PaymentMethod::BankTransfer->value,
                ])],
                'card_number' => ['nullable', 'string', 'max:32'],
                'card_expiry' => ['nullable', 'string', 'max:5'],
                'card_cvc' => ['nullable', 'string', 'max:4'],
                'card_holder' => ['nullable', 'string', 'max:255'],
            ])->validate();
        } catch (ValidationException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $exception->errors(), withInput: true);
        }

        $paymentMethod = (string) ($validated['payment_method'] ?? $this->enumValue($checkout->payment_method));

        if ($paymentMethod === '') {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'payment_method' => 'Payment method is required.',
            ], withInput: true);
        }

        try {
            $this->completeCheckout($checkout, $paymentMethod);
        } catch (InvalidCheckoutStateException $exception) {
            return $this->redirectToCheckoutWithErrors($checkoutId, $this->checkoutStateErrors($exception), withInput: true);
        } catch (Throwable) {
            return $this->redirectToCheckoutWithErrors($checkoutId, [
                'checkout' => 'Unable to complete payment right now.',
            ], withInput: true);
        }

        $request->session()->forget('cart_id');

        return redirect()->route('storefront.checkout.confirmation', ['checkoutId' => $checkoutId])
            ->with('status', 'Payment completed.');
    }

    public function confirmation(Request $request, int $checkoutId): View
    {
        $checkout = $this->resolveCheckout($request, $checkoutId);
        $totals = $this->normalizeTotals($checkout);
        $status = $this->checkoutStatus($checkout);
        $order = null;

        if ($status === CheckoutStatus::Completed->value) {
            $order = $this->findOrderForCheckout($checkout);
        }

        return view('storefront.checkout.confirmation', [
            'checkout' => $checkout,
            'totals' => $totals,
            'status' => $status,
            'order' => $order,
        ]);
    }

    private function resolveCheckout(Request $request, int $checkoutId): Checkout
    {
        /** @var Checkout $checkout */
        $checkout = Checkout::query()
            ->where('store_id', $this->currentStoreId($request))
            ->whereKey($checkoutId)
            ->with([
                'cart.lines.variant.product',
                'customer',
            ])
            ->firstOrFail();

        return $checkout;
    }

    private function completeCheckout(Checkout $checkout, string $paymentMethod): void
    {
        DB::transaction(function () use ($checkout, $paymentMethod): void {
            /** @var Checkout $checkoutRecord */
            $checkoutRecord = Checkout::query()
                ->whereKey($checkout->id)
                ->lockForUpdate()
                ->with([
                    'cart.lines.variant.product',
                ])
                ->firstOrFail();

            $status = $this->checkoutStatus($checkoutRecord);

            if ($status === CheckoutStatus::Completed->value) {
                return;
            }

            if ($status !== CheckoutStatus::PaymentSelected->value) {
                throw InvalidCheckoutStateException::invalidTransition($checkoutRecord, [CheckoutStatus::PaymentSelected->value]);
            }

            $cart = $checkoutRecord->cart;

            if (! $cart instanceof Cart) {
                throw new InvalidArgumentException('Cart not found for checkout.');
            }

            $finalizedDiscount = $this->lockDiscountForFinalization($checkoutRecord);

            if ($finalizedDiscount === null && $this->hasDiscountCode($checkoutRecord)) {
                $checkoutRecord->discount_code = null;
                $checkoutRecord->save();
            }

            try {
                $pricingResult = $this->checkoutService->computeTotals($checkoutRecord);
            } catch (InvalidDiscountException) {
                $finalizedDiscount = null;
                $checkoutRecord->discount_code = null;
                $checkoutRecord->save();

                $pricingResult = $this->checkoutService->computeTotals($checkoutRecord);
            }

            $totals = $this->pricingTotals($pricingResult);

            $cart->load(['lines.variant.product']);

            /** @var Collection<int, CartLine> $cartLines */
            $cartLines = $cart->lines
                ->sortBy('id')
                ->values();

            $lineDiscountAmounts = $this->lineDiscountAmountsFromCartLines($cartLines);

            $isBankTransfer = $paymentMethod === PaymentMethod::BankTransfer->value;

            $order = Order::query()->create([
                'store_id' => (int) $checkoutRecord->store_id,
                'customer_id' => $checkoutRecord->customer_id,
                'checkout_id' => (int) $checkoutRecord->id,
                'order_number' => $this->nextOrderNumber((int) $checkoutRecord->store_id),
                'payment_method' => $paymentMethod,
                'status' => $isBankTransfer ? 'pending' : 'paid',
                'financial_status' => $isBankTransfer ? 'pending' : 'paid',
                'fulfillment_status' => 'unfulfilled',
                'currency' => (string) $totals['currency'],
                'subtotal_amount' => (int) $totals['subtotal'],
                'discount_amount' => (int) $totals['discount'],
                'shipping_amount' => (int) $totals['shipping'],
                'tax_amount' => (int) $totals['tax'],
                'total_amount' => (int) $totals['total'],
                'email' => $checkoutRecord->email,
                'billing_address_json' => $checkoutRecord->billing_address_json,
                'shipping_address_json' => $checkoutRecord->shipping_address_json,
                'placed_at' => now(),
            ]);

            /** @var CartLine $line */
            foreach ($cartLines as $line) {
                $variant = $line->variant;
                $product = $variant?->product;
                $lineId = (int) $line->id;
                $lineDiscountAmount = (int) ($lineDiscountAmounts[$lineId] ?? 0);

                $order->lines()->create([
                    'product_id' => $product?->id,
                    'variant_id' => $variant?->id,
                    'title_snapshot' => (string) ($product?->title ?? 'Product'),
                    'sku_snapshot' => $variant?->sku,
                    'quantity' => (int) $line->quantity,
                    'unit_price_amount' => (int) $line->unit_price_amount,
                    'total_amount' => (int) $line->line_total_amount,
                    'tax_lines_json' => [],
                    'discount_allocations_json' => $this->orderLineDiscountAllocations(
                        discount: $finalizedDiscount,
                        lineDiscountAmount: $lineDiscountAmount,
                    ),
                ]);
            }

            $this->checkoutService->commitReservedInventoryForCheckout($checkoutRecord);

            if ($finalizedDiscount instanceof Discount) {
                $finalizedDiscount->increment('usage_count');
            }

            $order->payments()->create([
                'provider' => 'mock',
                'method' => $paymentMethod,
                'provider_payment_id' => 'mock_'.Str::lower(Str::random(16)),
                'status' => $isBankTransfer ? 'pending' : 'captured',
                'amount' => (int) $order->total_amount,
                'currency' => (string) $order->currency,
                'raw_json_encrypted' => null,
                'created_at' => now(),
            ]);

            $cart->status = CartStatus::Converted;
            $cart->save();

            $checkoutRecord->status = CheckoutStatus::Completed;
            $checkoutRecord->payment_method = $paymentMethod;
            $checkoutRecord->totals_json = $totals;
            $checkoutRecord->save();
        });
    }

    private function hasDiscountCode(Checkout $checkout): bool
    {
        return is_string($checkout->discount_code) && trim($checkout->discount_code) !== '';
    }

    private function lockDiscountForFinalization(Checkout $checkout): ?Discount
    {
        if (! $this->hasDiscountCode($checkout)) {
            return null;
        }

        $discountCode = trim((string) $checkout->discount_code);

        /** @var Discount|null $discount */
        $discount = Discount::query()
            ->where('store_id', (int) $checkout->store_id)
            ->whereRaw('lower(code) = ?', [Str::lower($discountCode)])
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->lockForUpdate()
            ->first();

        if (! $discount instanceof Discount) {
            return null;
        }

        if ($discount->usage_limit !== null && (int) $discount->usage_count >= (int) $discount->usage_limit) {
            return null;
        }

        return $discount;
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @return array<int, int>
     */
    private function lineDiscountAmountsFromCartLines(Collection $lines): array
    {
        if ($lines->isEmpty()) {
            return [];
        }

        /** @var array<int, int> $allocations */
        $allocations = [];

        /** @var CartLine $line */
        foreach ($lines as $line) {
            $lineId = (int) $line->id;
            if ($lineId <= 0) {
                continue;
            }

            $lineSubtotal = max(0, (int) $line->line_subtotal_amount);
            $lineDiscount = max(0, min($lineSubtotal, (int) $line->line_discount_amount));

            if ($lineDiscount <= 0) {
                continue;
            }

            $allocations[$lineId] = $lineDiscount;
        }

        return $allocations;
    }

    /**
     * @return list<array{discount_id: int, code: string, amount: int}>
     */
    private function orderLineDiscountAllocations(?Discount $discount, int $lineDiscountAmount): array
    {
        if (! $discount instanceof Discount || $lineDiscountAmount <= 0) {
            return [];
        }

        return [[
            'discount_id' => (int) $discount->id,
            'code' => (string) ($discount->code ?? ''),
            'amount' => $lineDiscountAmount,
        ]];
    }

    /**
     * @param  array<string, list<string>|string>  $errors
     */
    private function redirectToCheckoutWithErrors(int $checkoutId, array $errors, bool $withInput = false): RedirectResponse
    {
        $redirect = redirect()->route('storefront.checkout.show', ['checkoutId' => $checkoutId])
            ->withErrors($errors);

        if ($withInput) {
            $redirect->withInput();
        }

        return $redirect;
    }

    /**
     * @return array<string, string>
     */
    private function checkoutStateErrors(InvalidCheckoutStateException $exception): array
    {
        return match ($exception->reasonCode) {
            'invalid_shipping_method' => ['shipping_method_id' => 'The selected shipping method is invalid.'],
            'shipping_address_required', 'missing_address_field', 'unserviceable_address' => ['shipping_address' => $exception->getMessage()],
            'invalid_payment_method' => ['payment_method' => $exception->getMessage()],
            default => ['checkout' => $exception->getMessage()],
        };
    }

    /**
     * @return array<int, array{id: int, name: string, type: string, amount: int, currency: string}>
     */
    private function availableShippingMethods(Checkout $checkout): array
    {
        try {
            return $this->checkoutService->availableShippingMethods($checkout)
                ->map(function (ShippingRateQuote $quote) use ($checkout): array {
                    return [
                        'id' => $quote->rateId,
                        'name' => $quote->name,
                        'type' => $quote->type->value,
                        'amount' => $quote->amount,
                        'currency' => (string) ($checkout->cart?->currency ?? 'USD'),
                    ];
                })
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function currentDiscount(Checkout $checkout): ?Discount
    {
        if (! is_string($checkout->discount_code) || trim($checkout->discount_code) === '') {
            return null;
        }

        /** @var Discount|null $discount */
        $discount = Discount::query()
            ->where('store_id', (int) $checkout->store_id)
            ->whereRaw('lower(code) = ?', [Str::lower(trim($checkout->discount_code))])
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->first();

        return $discount;
    }

    private function cartRequiresShipping(Checkout $checkout): bool
    {
        $cart = $checkout->cart;

        if (! $cart instanceof Cart) {
            return false;
        }

        return $cart->lines->contains(static function (CartLine $line): bool {
            return (bool) ($line->variant?->requires_shipping ?? false);
        });
    }

    private function isExpiredCheckout(Checkout $checkout): bool
    {
        $status = $this->checkoutStatus($checkout);

        if ($status === CheckoutStatus::Completed->value) {
            return false;
        }

        $expired = $status === CheckoutStatus::Expired->value
            || ($checkout->expires_at !== null && $checkout->expires_at->isPast());

        if ($expired && $status !== CheckoutStatus::Expired->value) {
            $checkout->status = CheckoutStatus::Expired;
            $checkout->save();

            if ($status === CheckoutStatus::PaymentSelected->value) {
                $this->checkoutService->releaseReservedInventoryForCheckout($checkout);
            }
        }

        return $expired;
    }

    /**
     * @param  array<string, mixed>  $totals
     * @return array{subtotal_amount: int, discount_amount: int, shipping_amount: int, tax_amount: int, total_amount: int, currency: string}
     */
    private function totalsMap(array $totals): array
    {
        $currency = $totals['currency'] ?? 'USD';

        return [
            'subtotal_amount' => (int) ($totals['subtotal_amount'] ?? $totals['subtotal'] ?? 0),
            'discount_amount' => (int) ($totals['discount_amount'] ?? $totals['discount'] ?? 0),
            'shipping_amount' => (int) ($totals['shipping_amount'] ?? $totals['shipping'] ?? 0),
            'tax_amount' => (int) ($totals['tax_amount'] ?? $totals['tax'] ?? 0),
            'total_amount' => (int) ($totals['total_amount'] ?? $totals['total'] ?? 0),
            'currency' => is_string($currency) && $currency !== '' ? $currency : 'USD',
        ];
    }

    /**
     * @return array{subtotal_amount: int, discount_amount: int, shipping_amount: int, tax_amount: int, total_amount: int, currency: string}
     */
    private function normalizeTotals(Checkout $checkout): array
    {
        $totals = is_array($checkout->totals_json) ? $checkout->totals_json : [];

        if (! isset($totals['currency'])) {
            $totals['currency'] = $checkout->cart?->currency ?? 'USD';
        }

        return $this->totalsMap($totals);
    }

    /**
     * @return array{subtotal: int, discount: int, shipping: int, tax: int, total: int, currency: string}
     */
    private function pricingTotals(PricingResult $result): array
    {
        $payload = $result->toArray();

        return [
            'subtotal' => (int) ($payload['subtotal'] ?? 0),
            'discount' => (int) ($payload['discount'] ?? 0),
            'shipping' => (int) ($payload['shipping'] ?? 0),
            'tax' => (int) ($payload['tax'] ?? 0),
            'total' => (int) ($payload['total'] ?? 0),
            'currency' => (string) ($payload['currency'] ?? 'USD'),
        ];
    }

    private function checkoutStatus(Checkout $checkout): string
    {
        $status = $checkout->status;

        if ($status instanceof CheckoutStatus) {
            return $status->value;
        }

        return (string) $status;
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof PaymentMethod) {
            return $value->value;
        }

        if (! is_string($value)) {
            return null;
        }

        return $value;
    }

    private function findOrderForCheckout(Checkout $checkout): ?Order
    {
        $order = Order::query()
            ->where('store_id', (int) $checkout->store_id)
            ->where('checkout_id', (int) $checkout->id)
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->first();

        if ($order instanceof Order) {
            return $order;
        }

        $totals = $this->normalizeTotals($checkout);
        $orderQuery = Order::query()
            ->where('store_id', (int) $checkout->store_id)
            ->where('total_amount', (int) $totals['total_amount'])
            ->whereNull('checkout_id');

        if (is_numeric($checkout->customer_id)) {
            $orderQuery->where('customer_id', (int) $checkout->customer_id);
        } elseif (is_string($checkout->email) && $checkout->email !== '') {
            $orderQuery->where('email', $checkout->email);
        }

        /** @var Order|null $order */
        $order = $orderQuery
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->first();

        return $order;
    }

    private function nextOrderNumber(int $storeId): string
    {
        $max = (int) Order::query()
            ->where('store_id', $storeId)
            ->selectRaw("MAX(CAST(CASE WHEN order_number LIKE '#%' THEN SUBSTR(order_number, 2) ELSE order_number END AS INTEGER)) AS max_order")
            ->value('max_order');

        $next = $max > 0 ? $max + 1 : 1001;

        return '#'.$next;
    }
}
