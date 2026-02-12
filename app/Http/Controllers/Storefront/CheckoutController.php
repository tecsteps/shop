<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\DiscountValidationException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingRate;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends StorefrontController
{
    public function __construct(
        \App\Support\CurrentStore $currentStore,
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly DiscountService $discountService,
        private readonly InventoryService $inventoryService,
    ) {
        parent::__construct($currentStore);
    }

    public function create(Request $request): RedirectResponse
    {
        $cart = $this->resolveCart($request, false);

        if (! $cart || ! $cart->lines()->exists()) {
            return redirect()->route('storefront.cart.show')->withErrors([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $cart->load('lines');

        $customer = $this->customer();
        $discountCode = $request->session()->get($this->discountSessionKey($this->currentStore()));
        $discountCode = is_string($discountCode) ? $discountCode : null;

        $totals = $this->cartService->totals($cart);

        $checkout = $this->createWithCheckoutService($cart, (string) ($customer?->email ?? ''));

        if (! $checkout) {
            $checkout = Checkout::query()->create([
                'cart_id' => $cart->id,
                'customer_id' => $customer?->id,
                'status' => CheckoutStatus::Started,
                'email' => $customer?->email,
                'discount_code' => $discountCode,
                'totals_json' => [
                    'subtotal' => $totals['subtotal'],
                    'discount' => $totals['discount'],
                    'shipping' => 0,
                    'tax' => 0,
                    'total' => $totals['total'],
                    'currency' => $totals['currency'],
                ],
            ]);
        }

        if ($discountCode) {
            $checkout->discount_code = strtoupper($discountCode);
            $checkout->save();
        }

        return redirect()->route('storefront.checkout.show', ['checkoutId' => $checkout->id]);
    }

    public function show(Request $request, int $checkoutId)
    {
        $checkout = $this->checkoutForCurrentStore($checkoutId);

        $savedAddresses = $this->customer()
            ? CustomerAddress::query()
                ->where('customer_id', $this->customer()->id)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get()
            : collect();

        $shippingAddress = is_array($checkout->shipping_address_json) && $checkout->shipping_address_json !== []
            ? $checkout->shipping_address_json
            : ($savedAddresses->firstWhere('is_default', true)?->address_json ?? []);

        $billingAddress = is_array($checkout->billing_address_json) && $checkout->billing_address_json !== []
            ? $checkout->billing_address_json
            : $shippingAddress;

        $requiresShipping = $this->requiresShipping($checkout->cart);
        $availableRates = $requiresShipping
            ? $this->availableShippingRates($shippingAddress)
            : collect();

        $paymentMethod = $checkout->payment_method?->value ?? PaymentMethod::CreditCard->value;

        $totals = is_array($checkout->totals_json) && $checkout->totals_json !== []
            ? $checkout->totals_json
            : $this->previewTotals($checkout->cart, null, null, $checkout->discount_code);

        return view('storefront.checkout.show', $this->storefrontViewData($request, [
            'checkout' => $checkout,
            'savedAddresses' => $savedAddresses,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'availableRates' => $availableRates,
            'requiresShipping' => $requiresShipping,
            'paymentMethod' => $paymentMethod,
            'totals' => $totals,
            'title' => 'Checkout',
            'metaDescription' => 'Secure checkout.',
        ]));
    }

    public function submit(Request $request, int $checkoutId): RedirectResponse
    {
        $checkout = $this->checkoutForCurrentStore($checkoutId);
        $cart = $checkout->cart;
        $cart->load([
            'lines.variant.product',
            'lines.variant.inventoryItem',
            'lines.variant.optionValues.option',
        ]);

        $requiresShipping = $this->requiresShipping($cart);

        $rules = [
            'email' => ['required', 'email:rfc,dns'],
            'payment_method' => ['required', Rule::in([
                PaymentMethod::CreditCard->value,
                PaymentMethod::Paypal->value,
                PaymentMethod::BankTransfer->value,
            ])],
            'discount_code' => ['nullable', 'string', 'max:100'],
            'billing_same_as_shipping' => ['nullable', 'boolean'],
            'shipping.first_name' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:100'],
            'shipping.last_name' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:100'],
            'shipping.address1' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:255'],
            'shipping.address2' => ['nullable', 'string', 'max:255'],
            'shipping.company' => ['nullable', 'string', 'max:255'],
            'shipping.city' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:120'],
            'shipping.province' => ['nullable', 'string', 'max:120'],
            'shipping.province_code' => ['nullable', 'string', 'max:10'],
            'shipping.country' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:120'],
            'shipping.country_code' => [$requiresShipping ? 'required' : 'nullable', 'string', 'size:2'],
            'shipping.zip' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:20'],
            'shipping.phone' => ['nullable', 'string', 'max:40'],
            'shipping_rate_id' => [$requiresShipping ? 'required' : 'nullable', 'integer'],
            'billing.first_name' => ['nullable', 'string', 'max:100'],
            'billing.last_name' => ['nullable', 'string', 'max:100'],
            'billing.address1' => ['nullable', 'string', 'max:255'],
            'billing.address2' => ['nullable', 'string', 'max:255'],
            'billing.company' => ['nullable', 'string', 'max:255'],
            'billing.city' => ['nullable', 'string', 'max:120'],
            'billing.province' => ['nullable', 'string', 'max:120'],
            'billing.province_code' => ['nullable', 'string', 'max:10'],
            'billing.country' => ['nullable', 'string', 'max:120'],
            'billing.country_code' => ['nullable', 'string', 'size:2'],
            'billing.zip' => ['nullable', 'string', 'max:20'],
            'billing.phone' => ['nullable', 'string', 'max:40'],
            'card.number' => ['nullable', 'string', 'max:25'],
            'card.name' => ['nullable', 'string', 'max:255'],
            'card.expiry' => ['nullable', 'string', 'max:5'],
            'card.cvc' => ['nullable', 'string', 'max:4'],
        ];

        $validated = $request->validate($rules);

        $paymentMethod = PaymentMethod::from((string) $validated['payment_method']);

        if ($paymentMethod === PaymentMethod::CreditCard) {
            $this->validateCardData($validated);
        }

        $shippingAddress = $this->sanitizeAddress($validated['shipping'] ?? []);
        $billingSame = (bool) ($validated['billing_same_as_shipping'] ?? true);
        $billingAddress = $billingSame
            ? $shippingAddress
            : $this->sanitizeAddress($validated['billing'] ?? []);

        if (! $billingSame && ($billingAddress['address1'] ?? '') === '') {
            throw ValidationException::withMessages([
                'billing.address1' => 'Billing address line 1 is required when billing differs from shipping.',
            ]);
        }

        $shippingRate = null;

        if ($requiresShipping) {
            $availableRates = $this->availableShippingRates($shippingAddress);
            $shippingRate = $availableRates->firstWhere('id', (int) ($validated['shipping_rate_id'] ?? 0));

            if (! $shippingRate instanceof ShippingRate) {
                throw ValidationException::withMessages([
                    'shipping_rate_id' => 'Please choose an available shipping method.',
                ]);
            }
        }

        $discountCode = trim((string) ($validated['discount_code'] ?? ''));

        try {
            $this->syncDiscountCode($request, $cart, $discountCode);
        } catch (DiscountValidationException $exception) {
            return back()->withInput()->withErrors([
                'discount_code' => $exception->getMessage(),
            ]);
        }

        $preview = $this->previewTotals($cart, $shippingRate, $shippingAddress, $discountCode !== '' ? $discountCode : $checkout->discount_code);

        try {
            DB::transaction(function () use (
                $request,
                $checkout,
                $paymentMethod,
                $validated,
                $shippingAddress,
                $billingAddress,
                $shippingRate,
                $discountCode,
                $preview,
                $requiresShipping,
            ): void {
                $checkout->email = (string) $validated['email'];
                $checkout->shipping_address_json = $shippingAddress;
                $checkout->billing_address_json = $billingAddress;
                $checkout->shipping_method_id = $requiresShipping ? $shippingRate?->id : null;
                $checkout->payment_method = $paymentMethod;
                $checkout->discount_code = $discountCode !== '' ? strtoupper($discountCode) : null;
                $checkout->totals_json = $preview;
                $checkout->expires_at = now()->addHours(24);
                $checkout->status = CheckoutStatus::PaymentSelected;

                if (! $checkout->customer_id && $this->customer()) {
                    $checkout->customer_id = $this->customer()->id;
                }

                $checkout->save();

                $serviceOrder = $this->completeWithCheckoutService($checkout, $validated);

                if ($serviceOrder instanceof Order) {
                    $order = $serviceOrder;
                } else {
                    $orderId = (int) ($checkout->totals_json['order_id'] ?? 0);
                    $existingOrder = $orderId > 0 ? Order::query()->find($orderId) : null;

                    $order = $existingOrder instanceof Order
                        ? $existingOrder
                        : $this->createOrderFromCheckout($checkout, $paymentMethod, $validated['card'] ?? []);
                }

                $totals = is_array($checkout->totals_json) ? $checkout->totals_json : [];
                $totals['order_id'] = $order->id;

                $checkout->totals_json = $totals;
                $checkout->status = CheckoutStatus::Completed;
                $checkout->save();

                $checkout->cart->status = CartStatus::Converted;
                $checkout->cart->save();

                $request->session()->forget($this->discountSessionKey($this->currentStore()));
                $request->session()->forget($this->cartService->sessionKey($this->currentStore()));
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Checkout submit failed.', [
                'checkout_id' => $checkout->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'payment' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unable to complete checkout.',
            ]);
        }

        return redirect()->route('storefront.checkout.confirmation', ['checkoutId' => $checkout->id]);
    }

    public function confirmation(Request $request, int $checkoutId)
    {
        $checkout = $this->checkoutForCurrentStore($checkoutId);

        $checkout->load([
            'cart.lines.variant.product',
            'cart.lines.variant.optionValues.option',
        ]);

        $orderId = (int) ($checkout->totals_json['order_id'] ?? 0);

        $order = $orderId > 0
            ? Order::query()->with(['lines', 'payments'])->find($orderId)
            : null;

        if (! $order) {
            $order = Order::query()
                ->where('email', $checkout->email)
                ->where('total_amount', (int) ($checkout->totals_json['total'] ?? 0))
                ->latest('id')
                ->with(['lines', 'payments'])
                ->first();
        }

        abort_if(! $order, 404);

        $customer = $this->customer();

        $canViewOrder = $customer && $order->customer_id === $customer->id;

        return view('storefront.checkout.confirmation', $this->storefrontViewData($request, [
            'checkout' => $checkout,
            'order' => $order,
            'canViewOrder' => $canViewOrder,
            'shippingAddressLines' => $this->formatAddressLines($order->shipping_address_json),
            'billingAddressLines' => $this->formatAddressLines($order->billing_address_json),
            'bankTransferPending' => $order->payment_method === PaymentMethod::BankTransfer
                && $order->financial_status === FinancialStatus::Pending,
            'title' => 'Order Confirmation',
            'metaDescription' => 'Thank you for your order.',
        ]));
    }

    protected function checkoutForCurrentStore(int $checkoutId): Checkout
    {
        $checkout = Checkout::query()
            ->with([
                'cart.lines.variant.product',
                'cart.lines.variant.inventoryItem',
                'cart.lines.variant.optionValues.option',
                'shippingMethod.zone',
            ])
            ->whereKey($checkoutId)
            ->firstOrFail();

        $customer = $this->customer();

        if ($checkout->customer_id && (! $customer || $checkout->customer_id !== $customer->id)) {
            abort(404);
        }

        return $checkout;
    }

    protected function resolveCart(Request $request, bool $create): ?Cart
    {
        $store = $this->currentStore();
        $customer = $this->customer();
        $sessionKey = $this->cartService->sessionKey($store);

        $sessionCartId = $request->session()->get($sessionKey);

        $sessionCart = is_numeric($sessionCartId)
            ? Cart::query()
                ->where('store_id', $store->id)
                ->whereKey((int) $sessionCartId)
                ->where('status', CartStatus::Active)
                ->first()
            : null;

        $customerCart = $customer
            ? Cart::query()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', CartStatus::Active)
                ->latest('id')
                ->first()
            : null;

        $cart = $customerCart ?: $sessionCart;

        if (! $cart && $create) {
            $cart = $this->cartService->create($store, $customer?->id, $store->default_currency);
        }

        if ($cart && $customer && ! $cart->customer_id) {
            $cart->customer_id = $customer->id;
            $cart->save();
        }

        if ($cart) {
            $request->session()->put($sessionKey, $cart->id);
        }

        return $cart;
    }

    protected function requiresShipping(Cart $cart): bool
    {
        $cart->loadMissing('lines.variant');

        return $cart->lines->contains(fn ($line): bool => (bool) $line->variant?->requires_shipping);
    }

    protected function availableShippingRates(array $shippingAddress)
    {
        $countryCode = $this->normalizeCountryCode($shippingAddress);

        if ($countryCode === null) {
            return collect();
        }

        return ShippingRate::query()
            ->where('is_active', true)
            ->whereHas('zone', function ($query) use ($countryCode): void {
                $query->whereJsonContains('countries_json', $countryCode);
            })
            ->with('zone')
            ->orderBy('name')
            ->get();
    }

    protected function normalizeCountryCode(array $address): ?string
    {
        $code = strtoupper(trim((string) ($address['country_code'] ?? '')));

        if ($code !== '' && strlen($code) === 2) {
            return $code;
        }

        $country = strtolower(trim((string) ($address['country'] ?? '')));

        if ($country === '') {
            return null;
        }

        return match ($country) {
            'germany' => 'DE',
            'austria' => 'AT',
            'france' => 'FR',
            'italy' => 'IT',
            'spain' => 'ES',
            'netherlands' => 'NL',
            'belgium' => 'BE',
            'poland' => 'PL',
            'united states', 'usa', 'us' => 'US',
            default => strlen($country) === 2 ? strtoupper($country) : null,
        };
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    protected function sanitizeAddress(array $input): array
    {
        return [
            'first_name' => trim((string) ($input['first_name'] ?? '')),
            'last_name' => trim((string) ($input['last_name'] ?? '')),
            'company' => trim((string) ($input['company'] ?? '')),
            'address1' => trim((string) ($input['address1'] ?? '')),
            'address2' => trim((string) ($input['address2'] ?? '')),
            'city' => trim((string) ($input['city'] ?? '')),
            'province' => trim((string) ($input['province'] ?? '')),
            'province_code' => strtoupper(trim((string) ($input['province_code'] ?? ''))),
            'country' => trim((string) ($input['country'] ?? '')),
            'country_code' => strtoupper(trim((string) ($input['country_code'] ?? ''))),
            'zip' => trim((string) ($input['zip'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function validateCardData(array $validated): void
    {
        $number = preg_replace('/\D+/', '', (string) ($validated['card']['number'] ?? ''));
        $name = trim((string) ($validated['card']['name'] ?? ''));
        $expiry = trim((string) ($validated['card']['expiry'] ?? ''));
        $cvc = trim((string) ($validated['card']['cvc'] ?? ''));

        $messages = [];

        if (strlen($number) !== 16) {
            $messages['card.number'] = 'Card number must contain 16 digits.';
        }

        if ($name === '') {
            $messages['card.name'] = 'Cardholder name is required.';
        }

        if (! preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $matches)) {
            $messages['card.expiry'] = 'Expiry must use MM/YY format.';
        } else {
            $month = (int) $matches[1];
            $year = (int) ('20'.$matches[2]);
            $expiresAt = Carbon::create($year, $month, 1)->endOfMonth();

            if ($expiresAt->isPast()) {
                $messages['card.expiry'] = 'Card expiry date must be in the future.';
            }
        }

        if (! preg_match('/^\d{3,4}$/', $cvc)) {
            $messages['card.cvc'] = 'CVC must contain 3 or 4 digits.';
        }

        if ($number === '4000000000000002') {
            $messages['payment'] = 'Payment declined: card was declined.';
        }

        if ($number === '4000000000009995') {
            $messages['payment'] = 'Payment declined: insufficient funds.';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    protected function syncDiscountCode(Request $request, Cart $cart, string $discountCode): void
    {
        if ($discountCode === '') {
            $this->discountService->clearCartDiscount($cart);
            $request->session()->forget($this->discountSessionKey($this->currentStore()));

            return;
        }

        $this->discountService->applyCodeToCart($cart, $discountCode);
        $request->session()->put($this->discountSessionKey($this->currentStore()), strtoupper($discountCode));
    }

    protected function previewTotals(Cart $cart, ?ShippingRate $shippingRate, ?array $shippingAddress, ?string $discountCode): array
    {
        $cart = Cart::query()->with(['lines.variant.inventoryItem', 'store.taxSetting'])->findOrFail($cart->id);

        $totals = $this->cartService->totals($cart);

        $discount = $totals['discount'];
        $shipping = $shippingRate instanceof ShippingRate ? (int) ($shippingRate->config_json['amount'] ?? 0) : 0;

        $discountModel = $discountCode
            ? $this->discountService->findCode($this->currentStore(), $discountCode)
            : null;

        if ($discountModel?->value_type === DiscountValueType::FreeShipping) {
            $shipping = 0;
        }

        $taxableAmount = max(0, ((int) $totals['subtotal']) - $discount + $shipping);
        $taxSetting = $this->currentStore()->taxSetting;
        $rate = (int) ($taxSetting?->config_json['default_rate_bps'] ?? 0);

        if ($rate <= 0) {
            $tax = 0;
            $total = $taxableAmount;
        } elseif ($taxSetting?->prices_include_tax) {
            $tax = (int) round($taxableAmount * ($rate / (10000 + $rate)));
            $total = $taxableAmount;
        } else {
            $tax = (int) round($taxableAmount * ($rate / 10000));
            $total = $taxableAmount + $tax;
        }

        return [
            'subtotal' => (int) $totals['subtotal'],
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'currency' => $totals['currency'],
            'shipping_country_code' => $shippingAddress ? $this->normalizeCountryCode($shippingAddress) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $cardData
     */
    protected function createOrderFromCheckout(Checkout $checkout, PaymentMethod $paymentMethod, array $cardData): Order
    {
        $existingOrderId = (int) ($checkout->totals_json['order_id'] ?? 0);

        if ($existingOrderId > 0) {
            $existing = Order::query()->find($existingOrderId);

            if ($existing instanceof Order) {
                return $existing;
            }
        }

        $totals = is_array($checkout->totals_json) ? $checkout->totals_json : [];

        $isPending = $paymentMethod === PaymentMethod::BankTransfer;

        $order = Order::query()->create([
            'customer_id' => $checkout->customer_id,
            'order_number' => $this->nextOrderNumber(),
            'payment_method' => $paymentMethod,
            'status' => $isPending ? OrderStatus::Pending : OrderStatus::Paid,
            'financial_status' => $isPending ? FinancialStatus::Pending : FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'currency' => (string) ($totals['currency'] ?? $checkout->cart->currency),
            'subtotal_amount' => (int) ($totals['subtotal'] ?? 0),
            'discount_amount' => (int) ($totals['discount'] ?? 0),
            'shipping_amount' => (int) ($totals['shipping'] ?? 0),
            'tax_amount' => (int) ($totals['tax'] ?? 0),
            'total_amount' => (int) ($totals['total'] ?? 0),
            'email' => $checkout->email,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_address_json' => $checkout->shipping_address_json,
            'placed_at' => now(),
        ]);

        foreach ($checkout->cart->lines as $line) {
            $variant = $line->variant;
            $optionLabel = $variant->optionValues
                ->map(fn ($value): string => $value->option->name.': '.$value->value)
                ->implode(', ');

            $titleSnapshot = trim($variant->product->title.($optionLabel !== '' ? " ({$optionLabel})" : ''));

            $order->lines()->create([
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'title_snapshot' => $titleSnapshot,
                'sku_snapshot' => $variant->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->line_total_amount,
                'tax_lines_json' => [],
                'discount_allocations_json' => [],
            ]);

            if ($isPending) {
                $this->inventoryService->reserve($variant, $line->quantity);
            } else {
                $this->inventoryService->commit($variant, $line->quantity);
            }
        }

        $rawPayload = null;

        if ($paymentMethod === PaymentMethod::CreditCard) {
            $number = preg_replace('/\D+/', '', (string) ($cardData['number'] ?? ''));
            $rawPayload = json_encode([
                'card_last4' => substr($number, -4),
            ], JSON_THROW_ON_ERROR);
        }

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'method' => $paymentMethod,
            'provider_payment_id' => 'mock_'.str()->lower(str()->random(14)),
            'status' => $isPending ? PaymentStatus::Pending : PaymentStatus::Captured,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'raw_json_encrypted' => $rawPayload,
        ]);

        if ($checkout->discount_code) {
            /** @var Discount|null $discount */
            $discount = $this->discountService->findCode($this->currentStore(), $checkout->discount_code);

            if ($discount) {
                $discount->usage_count += 1;
                $discount->save();
            }
        }

        return $order;
    }

    protected function nextOrderNumber(): string
    {
        $settings = (array) ($this->currentStore()->settings?->settings_json ?? []);
        $prefix = (string) ($settings['order_number_prefix'] ?? '#');
        $start = (int) ($settings['order_number_start'] ?? 1001);

        $maxNumeric = Order::query()
            ->pluck('order_number')
            ->map(function (string $orderNumber): int {
                $digits = preg_replace('/\D+/', '', $orderNumber);

                return is_string($digits) && $digits !== '' ? (int) $digits : 0;
            })
            ->max();

        $next = max($start, ((int) $maxNumeric) + 1);

        return $prefix.$next;
    }

    protected function completeWithCheckoutService(Checkout $checkout, array $validated): ?Order
    {
        try {
            $this->checkoutService->setAddress($checkout, [
                ...($validated['shipping'] ?? []),
            ], [
                ...($validated['billing'] ?? []),
            ], (bool) ($validated['billing_same_as_shipping'] ?? true), (string) $validated['email']);

            $shippingRateId = (int) ($validated['shipping_rate_id'] ?? 0);

            $this->checkoutService->setShippingMethod($checkout, $shippingRateId > 0 ? $shippingRateId : null);

            $this->checkoutService->selectPaymentMethod($checkout, (string) $validated['payment_method']);

            $result = $this->checkoutService->placeOrder($checkout, [
                'payment_method' => (string) $validated['payment_method'],
                'card_number' => (string) ($validated['card']['number'] ?? ''),
                'card_name' => (string) ($validated['card']['name'] ?? ''),
                'card_expiry' => (string) ($validated['card']['expiry'] ?? ''),
                'card_cvc' => (string) ($validated['card']['cvc'] ?? ''),
            ]);

            return $result instanceof Order ? $result : null;
        } catch (Throwable $exception) {
            Log::warning('CheckoutService integration failed, using fallback checkout flow.', [
                'checkout_id' => $checkout->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function createWithCheckoutService(Cart $cart, string $email): ?Checkout
    {
        try {
            $checkout = $this->checkoutService->createFromCart($cart, $email);

            return $checkout instanceof Checkout ? $checkout : null;
        } catch (Throwable $exception) {
            Log::warning('CheckoutService createFromCart integration failed, using fallback checkout create.', [
                'cart_id' => $cart->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
