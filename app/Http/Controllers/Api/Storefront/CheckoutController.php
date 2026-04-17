<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\InvalidCheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Checkouts\ApplyCheckoutDiscountRequest;
use App\Http\Requests\Storefront\Checkouts\PayCheckoutRequest;
use App\Http\Requests\Storefront\Checkouts\SelectCheckoutPaymentMethodRequest;
use App\Http\Requests\Storefront\Checkouts\SelectCheckoutShippingMethodRequest;
use App\Http\Requests\Storefront\Checkouts\StoreCheckoutRequest;
use App\Http\Requests\Storefront\Checkouts\UpdateCheckoutAddressRequest;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use ResolvesApiContext;

    public function store(StoreCheckoutRequest $request): JsonResponse
    {
        $store = $this->currentStoreModel($request);
        $validated = $request->validated();

        $cart = $this->findCart((int) $store->id, (int) $validated['cart_id']);

        if (! $cart instanceof Cart) {
            return response()->json([
                'message' => 'Cart not found.',
            ], 404);
        }

        if ($cart->lines->isEmpty()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'cart_id' => ['Cannot create checkout from an empty cart.'],
                ],
            ], 422);
        }

        $service = $this->resolveService('App\\Services\\CheckoutService');

        if ($service !== null) {
            try {
                if (method_exists($service, 'createFromCart')) {
                    $serviceCheckout = $service->createFromCart($cart, (string) $validated['email']);

                    if ($serviceCheckout instanceof Checkout) {
                        return response()->json($this->checkoutPayload($this->loadCheckout($serviceCheckout)), 201);
                    }
                }

                if (method_exists($service, 'create')) {
                    $serviceCheckout = $service->create($cart, (string) $validated['email']);

                    if ($serviceCheckout instanceof Checkout) {
                        return response()->json($this->checkoutPayload($this->loadCheckout($serviceCheckout)), 201);
                    }
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $checkout = Checkout::query()->create([
            'store_id' => (int) $store->id,
            'cart_id' => (int) $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => 'started',
            'email' => (string) $validated['email'],
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => $this->totalsFromCart($cart, 0, 0),
            'expires_at' => now()->addDay(),
        ]);

        return response()->json($this->checkoutPayload($this->loadCheckout($checkout)), 201);
    }

    public function show(Request $request, int $checkout): JsonResponse
    {
        $foundCheckout = $this->findCheckout($this->currentStoreId($request), $checkout);

        if (! $foundCheckout instanceof Checkout) {
            return response()->json([
                'message' => 'Checkout not found.',
            ], 404);
        }

        if ($this->isExpiredCheckout($foundCheckout)) {
            return response()->json([
                'message' => 'Checkout expired.',
                'error_code' => 'checkout_expired',
            ], 422);
        }

        $shippingMethods = $this->availableShippingMethods(
            (int) $foundCheckout->store_id,
            is_array($foundCheckout->shipping_address_json) ? $foundCheckout->shipping_address_json : null,
        );

        return response()->json($this->checkoutPayload($foundCheckout, $shippingMethods, $this->appliedDiscounts($foundCheckout)));
    }

    public function updateAddress(UpdateCheckoutAddressRequest $request, int $checkout): JsonResponse
    {
        $foundCheckout = $this->findCheckout($this->currentStoreId($request), $checkout);

        if (! $foundCheckout instanceof Checkout) {
            return response()->json([
                'message' => 'Checkout not found.',
            ], 404);
        }

        if ($this->isExpiredCheckout($foundCheckout)) {
            return response()->json([
                'message' => 'Checkout expired.',
                'error_code' => 'checkout_expired',
            ], 422);
        }

        $validated = $request->validated();
        $shippingAddress = isset($validated['shipping_address']) && is_array($validated['shipping_address'])
            ? $validated['shipping_address']
            : null;

        $useShippingAsBilling = (bool) ($validated['use_shipping_as_billing'] ?? true);
        $billingAddress = $useShippingAsBilling
            ? $shippingAddress
            : ((isset($validated['billing_address']) && is_array($validated['billing_address'])) ? $validated['billing_address'] : null);

        if ($this->cartRequiresShipping($foundCheckout) && ! is_array($shippingAddress)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'shipping_address' => ['Shipping address is required.'],
                ],
            ], 422);
        }

        $service = $this->resolveService('App\\Services\\CheckoutService');

        if ($service !== null && method_exists($service, 'setAddress')) {
            try {
                $service->setAddress($foundCheckout, [
                    'shipping_address' => $shippingAddress,
                    'billing_address' => $billingAddress,
                    'use_shipping_as_billing' => $useShippingAsBilling,
                ]);

                $reloaded = $this->loadCheckout($foundCheckout);
                $shippingMethods = $this->availableShippingMethods(
                    (int) $reloaded->store_id,
                    is_array($reloaded->shipping_address_json) ? $reloaded->shipping_address_json : null,
                );

                return response()->json($this->checkoutPayload($reloaded, $shippingMethods, $this->appliedDiscounts($reloaded)));
            } catch (\Throwable) {
                // Fall back to direct implementation below.
            }
        }

        $foundCheckout->shipping_address_json = $shippingAddress;
        $foundCheckout->billing_address_json = $billingAddress;
        $foundCheckout->status = 'addressed';

        $shippingMethods = $this->availableShippingMethods((int) $foundCheckout->store_id, $shippingAddress);

        if ($this->cartRequiresShipping($foundCheckout) && $shippingMethods === []) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'shipping_address' => ['No shipping methods are available for this address.'],
                ],
            ], 422);
        }

        $discount = $this->currentDiscount($foundCheckout);
        $foundCheckout->totals_json = $this->recalculateTotals($foundCheckout, $discount);
        $foundCheckout->save();

        return response()->json($this->checkoutPayload($this->loadCheckout($foundCheckout), $shippingMethods, $this->appliedDiscounts($foundCheckout)));
    }

    public function selectShippingMethod(SelectCheckoutShippingMethodRequest $request, int $checkout): JsonResponse
    {
        $foundCheckout = $this->findCheckout($this->currentStoreId($request), $checkout);

        if (! $foundCheckout instanceof Checkout) {
            return response()->json([
                'message' => 'Checkout not found.',
            ], 404);
        }

        if ($this->isExpiredCheckout($foundCheckout)) {
            return response()->json([
                'message' => 'Checkout expired.',
                'error_code' => 'checkout_expired',
            ], 422);
        }

        $validated = $request->validated();
        $shippingMethodId = (int) $validated['shipping_method_id'];

        $shippingMethods = $this->availableShippingMethods(
            (int) $foundCheckout->store_id,
            is_array($foundCheckout->shipping_address_json) ? $foundCheckout->shipping_address_json : null,
        );

        $selected = collect($shippingMethods)->firstWhere('id', $shippingMethodId);

        if ($this->cartRequiresShipping($foundCheckout) && ! is_array($selected)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'shipping_method_id' => ['The selected shipping method is invalid.'],
                ],
            ], 422);
        }

        $service = $this->resolveService('App\\Services\\CheckoutService');

        if ($service !== null && method_exists($service, 'setShippingMethod')) {
            try {
                $service->setShippingMethod($foundCheckout, $shippingMethodId);

                $reloaded = $this->loadCheckout($foundCheckout);

                return response()->json($this->checkoutPayload($reloaded, $shippingMethods, $this->appliedDiscounts($reloaded)));
            } catch (\Throwable) {
                // Fall back to direct implementation below.
            }
        }

        $shippingAmount = is_array($selected)
            ? (int) ($selected['price_amount'] ?? 0)
            : 0;

        $foundCheckout->shipping_method_id = $this->cartRequiresShipping($foundCheckout)
            ? $shippingMethodId
            : null;
        $foundCheckout->status = 'shipping_selected';
        $foundCheckout->totals_json = $this->recalculateTotals($foundCheckout, $this->currentDiscount($foundCheckout), $shippingAmount);
        $foundCheckout->save();

        return response()->json($this->checkoutPayload($this->loadCheckout($foundCheckout), $shippingMethods, $this->appliedDiscounts($foundCheckout)));
    }

    public function selectPaymentMethod(SelectCheckoutPaymentMethodRequest $request, int $checkout): JsonResponse
    {
        $foundCheckout = $this->findCheckout($this->currentStoreId($request), $checkout);

        if (! $foundCheckout instanceof Checkout) {
            return response()->json([
                'message' => 'Checkout not found.',
            ], 404);
        }

        if ($this->isExpiredCheckout($foundCheckout)) {
            return response()->json([
                'message' => 'Checkout expired.',
                'error_code' => 'checkout_expired',
            ], 422);
        }

        if (! in_array((string) $this->enumValue($foundCheckout->status), ['addressed', 'shipping_selected', 'payment_selected'], true)) {
            return response()->json([
                'message' => 'Checkout is not ready for payment selection.',
                'error_code' => 'checkout_invalid_state',
            ], 409);
        }

        $validated = $request->validated();
        $paymentMethod = (string) $validated['payment_method'];

        $service = $this->resolveService('App\\Services\\CheckoutService');

        if ($service !== null && method_exists($service, 'selectPaymentMethod')) {
            try {
                $service->selectPaymentMethod($foundCheckout, $paymentMethod);

                $reloaded = $this->loadCheckout($foundCheckout);

                return response()->json($this->checkoutPayload($reloaded, $this->availableShippingMethods((int) $reloaded->store_id, is_array($reloaded->shipping_address_json) ? $reloaded->shipping_address_json : null), $this->appliedDiscounts($reloaded)));
            } catch (\Throwable) {
                // Fall back to direct implementation below.
            }
        }

        $foundCheckout->payment_method = $paymentMethod;
        $foundCheckout->status = 'payment_selected';
        $foundCheckout->totals_json = $this->recalculateTotals($foundCheckout, $this->currentDiscount($foundCheckout));
        $foundCheckout->save();

        return response()->json($this->checkoutPayload($this->loadCheckout($foundCheckout), $this->availableShippingMethods((int) $foundCheckout->store_id, is_array($foundCheckout->shipping_address_json) ? $foundCheckout->shipping_address_json : null), $this->appliedDiscounts($foundCheckout)));
    }

    public function applyDiscount(ApplyCheckoutDiscountRequest $request, int $checkout): JsonResponse
    {
        $foundCheckout = $this->findCheckout($this->currentStoreId($request), $checkout);

        if (! $foundCheckout instanceof Checkout) {
            return response()->json([
                'message' => 'Checkout not found.',
            ], 404);
        }

        if ($this->isExpiredCheckout($foundCheckout)) {
            return response()->json([
                'message' => 'Checkout expired.',
                'error_code' => 'checkout_expired',
            ], 422);
        }

        $code = (string) $request->validated('code');
        $service = $this->resolveService('App\\Services\\CheckoutService');

        if ($service === null || ! method_exists($service, 'applyDiscount')) {
            return response()->json([
                'message' => 'Discount service is currently unavailable.',
                'error_code' => 'discount_service_unavailable',
            ], 503);
        }

        try {
            $service->applyDiscount($foundCheckout, $code);
        } catch (InvalidDiscountException $exception) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'code' => [$exception->getMessage()],
                ],
            ], 422);
        } catch (InvalidCheckoutStateException) {
            return response()->json([
                'message' => 'Checkout is not eligible for discount changes.',
                'error_code' => 'checkout_invalid_state',
            ], 409);
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Unable to apply discount at this time.',
                'error_code' => 'discount_apply_failed',
            ], 500);
        }

        $foundCheckout = $this->loadCheckout($foundCheckout);
        $shippingMethods = $this->availableShippingMethods(
            (int) $foundCheckout->store_id,
            is_array($foundCheckout->shipping_address_json) ? $foundCheckout->shipping_address_json : null,
        );

        return response()->json($this->checkoutPayload($foundCheckout, $shippingMethods, $this->appliedDiscounts($foundCheckout)));
    }

    public function removeDiscount(Request $request, int $checkout): JsonResponse
    {
        $foundCheckout = $this->findCheckout($this->currentStoreId($request), $checkout);

        if (! $foundCheckout instanceof Checkout) {
            return response()->json([
                'message' => 'Checkout not found.',
            ], 404);
        }

        if ($this->isExpiredCheckout($foundCheckout)) {
            return response()->json([
                'message' => 'Checkout expired.',
                'error_code' => 'checkout_expired',
            ], 422);
        }

        if (! is_string($foundCheckout->discount_code) || $foundCheckout->discount_code === '') {
            return response()->json([
                'message' => 'Discount not found on checkout.',
            ], 404);
        }

        $foundCheckout->discount_code = null;
        $foundCheckout->totals_json = $this->recalculateTotals($foundCheckout, null);
        $foundCheckout->save();

        $shippingMethods = $this->availableShippingMethods(
            (int) $foundCheckout->store_id,
            is_array($foundCheckout->shipping_address_json) ? $foundCheckout->shipping_address_json : null,
        );

        return response()->json($this->checkoutPayload($this->loadCheckout($foundCheckout), $shippingMethods, []));
    }

    public function pay(PayCheckoutRequest $request, int $checkout): JsonResponse
    {
        $foundCheckout = $this->findCheckout($this->currentStoreId($request), $checkout);

        if (! $foundCheckout instanceof Checkout) {
            return response()->json([
                'message' => 'Checkout not found.',
            ], 404);
        }

        if ($this->isExpiredCheckout($foundCheckout)) {
            return response()->json([
                'message' => 'Checkout expired.',
                'error_code' => 'checkout_expired',
            ], 422);
        }

        if ((string) $this->enumValue($foundCheckout->status) === 'completed') {
            $existingOrder = $this->findOrderByCheckoutId(
                storeId: (int) $foundCheckout->store_id,
                checkoutId: (int) $foundCheckout->id,
            );

            if ($existingOrder instanceof Order) {
                return response()->json($this->paymentResponsePayload($foundCheckout, $existingOrder));
            }

            return response()->json([
                'message' => 'Checkout already completed.',
                'error_code' => 'checkout_already_completed',
            ], 409);
        }

        if ((string) $this->enumValue($foundCheckout->status) !== 'payment_selected') {
            return response()->json([
                'message' => 'Checkout is not ready for payment.',
                'error_code' => 'checkout_invalid_state',
            ], 409);
        }

        $validated = $request->validated();
        $paymentMethod = (string) ($validated['payment_method'] ?? $this->enumValue($foundCheckout->payment_method));

        if ($paymentMethod === '') {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'payment_method' => ['Payment method is required.'],
                ],
            ], 422);
        }

        $service = $this->resolveService('App\\Services\\CheckoutService');

        if ($service !== null && method_exists($service, 'completeCheckout')) {
            try {
                $result = $service->completeCheckout($foundCheckout, $validated);

                if ($result instanceof Order) {
                    return response()->json($this->paymentResponsePayload($foundCheckout, $result));
                }
            } catch (\Throwable) {
                // Fall back to direct implementation below.
            }
        }

        $order = DB::transaction(function () use ($foundCheckout, $paymentMethod): Order {
            /** @var Checkout $checkoutRecord */
            $checkoutRecord = Checkout::query()
                ->whereKey($foundCheckout->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingOrder = $this->findOrderByCheckoutId(
                storeId: (int) $checkoutRecord->store_id,
                checkoutId: (int) $checkoutRecord->id,
            );

            if ($existingOrder instanceof Order) {
                return $existingOrder;
            }

            $cart = $this->findCart((int) $checkoutRecord->store_id, (int) $checkoutRecord->cart_id);

            if (! $cart instanceof Cart) {
                abort(404, 'Cart not found.');
            }

            $finalizedDiscount = $this->lockDiscountForFinalization($checkoutRecord);

            if ($finalizedDiscount === null && $this->hasDiscountCode($checkoutRecord)) {
                $checkoutRecord->discount_code = null;
                $checkoutRecord->save();
            }

            $totals = $this->recalculateTotals($checkoutRecord, $finalizedDiscount);

            $checkoutService = $this->resolveService('App\\Services\\CheckoutService');

            if ($checkoutService instanceof CheckoutService) {
                try {
                    $totals = $checkoutService->computeTotals($checkoutRecord)->toArray();
                } catch (InvalidDiscountException) {
                    $finalizedDiscount = null;
                    $checkoutRecord->discount_code = null;
                    $checkoutRecord->save();

                    try {
                        $totals = $checkoutService->computeTotals($checkoutRecord)->toArray();
                    } catch (\Throwable) {
                        $totals = $this->recalculateTotals($checkoutRecord, null);
                    }
                } catch (\Throwable) {
                    $totals = $this->recalculateTotals($checkoutRecord, $finalizedDiscount);
                }
            }

            $checkoutRecord->totals_json = $totals;

            $cart->load(['lines.variant.product']);

            /** @var Collection<int, CartLine> $cartLines */
            $cartLines = $cart->lines
                ->sortBy('id')
                ->values();

            $lineDiscountAmounts = $this->lineDiscountAmountsFromCartLines($cartLines);

            $isBankTransfer = $paymentMethod === 'bank_transfer';

            $order = Order::query()->create([
                'store_id' => (int) $checkoutRecord->store_id,
                'customer_id' => $checkoutRecord->customer_id,
                'checkout_id' => (int) $checkoutRecord->id,
                'order_number' => $this->nextOrderNumber((int) $checkoutRecord->store_id),
                'payment_method' => $paymentMethod,
                'status' => $isBankTransfer ? 'pending' : 'paid',
                'financial_status' => $isBankTransfer ? 'pending' : 'paid',
                'fulfillment_status' => 'unfulfilled',
                'currency' => (string) ($totals['currency'] ?? $cart->currency),
                'subtotal_amount' => (int) ($totals['subtotal'] ?? 0),
                'discount_amount' => (int) ($totals['discount'] ?? 0),
                'shipping_amount' => (int) ($totals['shipping'] ?? 0),
                'tax_amount' => (int) ($totals['tax'] ?? 0),
                'total_amount' => (int) ($totals['total'] ?? 0),
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

            if ($checkoutService instanceof CheckoutService) {
                $checkoutService->commitReservedInventoryForCheckout($checkoutRecord);
            }

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

            $cart->status = 'converted';
            $cart->save();

            $checkoutRecord->status = 'completed';
            $checkoutRecord->payment_method = $paymentMethod;
            $checkoutRecord->save();

            return $order->fresh();
        });

        $reloadedCheckout = $this->loadCheckout($foundCheckout);

        return response()->json($this->paymentResponsePayload($reloadedCheckout, $order));
    }

    private function findOrderByCheckoutId(int $storeId, int $checkoutId): ?Order
    {
        /** @var Order|null $order */
        $order = Order::query()
            ->where('store_id', $storeId)
            ->where('checkout_id', $checkoutId)
            ->orderByDesc('id')
            ->first();

        return $order;
    }

    private function findCart(int $storeId, int $cartId): ?Cart
    {
        return Cart::query()
            ->where('store_id', $storeId)
            ->whereKey($cartId)
            ->with([
                'lines.variant.optionValues' => fn ($query) => $query->orderBy('position'),
                'lines.variant.product',
            ])
            ->first();
    }

    private function findCheckout(int $storeId, int $checkoutId): ?Checkout
    {
        return Checkout::query()
            ->where('store_id', $storeId)
            ->whereKey($checkoutId)
            ->with([
                'cart.lines.variant.optionValues' => fn ($query) => $query->orderBy('position'),
                'cart.lines.variant.product',
            ])
            ->first();
    }

    private function loadCheckout(Checkout $checkout): Checkout
    {
        $loaded = $this->findCheckout((int) $checkout->store_id, (int) $checkout->id);

        return $loaded instanceof Checkout ? $loaded : $checkout;
    }

    private function isExpiredCheckout(Checkout $checkout): bool
    {
        $status = (string) $this->enumValue($checkout->status);

        if ($status === 'completed') {
            return false;
        }

        $isExpired = $status === 'expired' || ($checkout->expires_at !== null && $checkout->expires_at->isPast());

        if ($isExpired && $status !== 'expired') {
            $checkout->status = 'expired';
            $checkout->save();

            $service = $this->resolveService('App\\Services\\CheckoutService');

            if ($status === 'payment_selected' && $service instanceof CheckoutService) {
                $service->releaseReservedInventoryForCheckout($checkout);
            }
        }

        return $isExpired;
    }

    private function cartRequiresShipping(Checkout $checkout): bool
    {
        $cart = $checkout->cart;

        if (! $cart instanceof Cart) {
            return false;
        }

        return $cart->lines->contains(function ($line): bool {
            return (bool) ($line->variant?->requires_shipping ?? false);
        });
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @return array<int, array<string, mixed>>
     */
    private function availableShippingMethods(int $storeId, ?array $address): array
    {
        if (! is_array($address)) {
            return [];
        }

        $countryCode = strtoupper((string) ($address['country_code'] ?? ''));
        $provinceCode = strtoupper((string) ($address['province_code'] ?? ''));

        if ($countryCode === '') {
            return [];
        }

        $zones = ShippingZone::query()
            ->where('store_id', $storeId)
            ->with(['rates' => fn ($query) => $query->where('is_active', true)])
            ->get();

        $methods = [];

        foreach ($zones as $zone) {
            $countries = array_map('strtoupper', array_values(is_array($zone->countries_json) ? $zone->countries_json : []));
            $regions = array_map('strtoupper', array_values(is_array($zone->regions_json) ? $zone->regions_json : []));

            if (! in_array($countryCode, $countries, true)) {
                continue;
            }

            if ($regions !== []) {
                if ($provinceCode === '' || ! in_array($provinceCode, $regions, true)) {
                    continue;
                }
            }

            foreach ($zone->rates as $rate) {
                if (! $rate instanceof ShippingRate) {
                    continue;
                }

                $config = is_array($rate->config_json) ? $rate->config_json : [];

                $methods[] = [
                    'id' => (int) $rate->id,
                    'name' => (string) $rate->name,
                    'type' => (string) $this->enumValue($rate->type),
                    'price_amount' => $this->shippingAmountFromRate($rate),
                    'currency' => (string) ($config['currency'] ?? 'USD'),
                    'estimated_days_min' => isset($config['estimated_days_min']) ? (int) $config['estimated_days_min'] : null,
                    'estimated_days_max' => isset($config['estimated_days_max']) ? (int) $config['estimated_days_max'] : null,
                ];
            }
        }

        return $methods;
    }

    private function shippingAmountFromRate(ShippingRate $rate): int
    {
        $config = is_array($rate->config_json) ? $rate->config_json : [];
        $amount = $config['price_amount'] ?? null;

        if (! is_numeric($amount) && isset($config['tiers']) && is_array($config['tiers']) && $config['tiers'] !== []) {
            $firstTier = $config['tiers'][0];
            $amount = is_array($firstTier) ? ($firstTier['price_amount'] ?? 0) : 0;
        }

        return max(0, (int) ($amount ?? 0));
    }

    private function findDiscountByCode(int $storeId, string $code): ?Discount
    {
        return Discount::query()
            ->where('store_id', $storeId)
            ->whereRaw('LOWER(code) = ?', [Str::lower($code)])
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->first();
    }

    private function currentDiscount(Checkout $checkout): ?Discount
    {
        if (! $this->hasDiscountCode($checkout)) {
            return null;
        }

        return $this->findDiscountByCode((int) $checkout->store_id, trim((string) $checkout->discount_code));
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
            ->whereRaw('LOWER(code) = ?', [Str::lower($discountCode)])
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
     * @return array<int, array<string, mixed>>
     */
    private function appliedDiscounts(Checkout $checkout): array
    {
        $discount = $this->currentDiscount($checkout);

        if (! $discount instanceof Discount) {
            return [];
        }

        $totals = is_array($checkout->totals_json) ? $checkout->totals_json : [];

        return [[
            'code' => (string) ($discount->code ?? ''),
            'type' => (string) $this->enumValue($discount->value_type),
            'value_amount' => (int) $discount->value_amount,
            'applied_amount' => (int) ($totals['discount'] ?? 0),
            'description' => null,
        ]];
    }

    /**
     * @return array<string, int|string>
     */
    private function totalsFromCart(Cart $cart, int $discount, int $shipping): array
    {
        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');
        $tax = 0;
        $total = max(0, $subtotal - $discount + $shipping + $tax);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'currency' => (string) $cart->currency,
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function recalculateTotals(Checkout $checkout, ?Discount $discount = null, ?int $shippingAmount = null): array
    {
        $cart = $checkout->cart;

        if (! $cart instanceof Cart) {
            return [
                'subtotal' => 0,
                'discount' => 0,
                'shipping' => 0,
                'tax' => 0,
                'total' => 0,
                'currency' => 'USD',
            ];
        }

        $subtotal = (int) $cart->lines->sum('line_subtotal_amount');

        if ($shippingAmount === null) {
            $shippingAmount = 0;

            if ($checkout->shipping_method_id !== null) {
                $rate = ShippingRate::query()->find((int) $checkout->shipping_method_id);

                if ($rate instanceof ShippingRate) {
                    $shippingAmount = $this->shippingAmountFromRate($rate);
                }
            }
        }

        $discountAmount = 0;

        if ($discount instanceof Discount) {
            $valueType = (string) $this->enumValue($discount->value_type);

            if ($valueType === 'percent') {
                $discountAmount = (int) floor($subtotal * ((int) $discount->value_amount / 100));
            } elseif ($valueType === 'fixed') {
                $discountAmount = min($subtotal, (int) $discount->value_amount);
            } elseif ($valueType === 'free_shipping') {
                $discountAmount = $shippingAmount;
                $shippingAmount = 0;
            }
        }

        $tax = 0;
        $total = max(0, $subtotal - $discountAmount + $shippingAmount + $tax);

        return [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'shipping' => $shippingAmount,
            'tax' => $tax,
            'total' => $total,
            'currency' => (string) $cart->currency,
        ];
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

    /**
     * @param  array<int, array<string, mixed>>  $shippingMethods
     * @param  array<int, array<string, mixed>>  $appliedDiscounts
     * @return array<string, mixed>
     */
    private function checkoutPayload(Checkout $checkout, array $shippingMethods = [], array $appliedDiscounts = []): array
    {
        $cart = $checkout->cart;
        $lines = [];

        if ($cart instanceof Cart) {
            $lines = $cart->lines->map(function ($line): array {
                $variant = $line->variant;
                $product = $variant?->product;

                $optionValues = $variant !== null && $variant->relationLoaded('optionValues')
                    ? $variant->optionValues->pluck('value')->filter()->values()->all()
                    : [];

                $variantTitle = $optionValues !== []
                    ? implode(' / ', $optionValues)
                    : ($variant?->is_default ? 'Default' : null);

                return [
                    'variant_id' => (int) $line->variant_id,
                    'product_title' => $product?->title,
                    'variant_title' => $variantTitle,
                    'sku' => $variant?->sku,
                    'quantity' => (int) $line->quantity,
                    'unit_price_amount' => (int) $line->unit_price_amount,
                    'line_total_amount' => (int) $line->line_total_amount,
                ];
            })->values()->all();
        }

        $totals = is_array($checkout->totals_json)
            ? $checkout->totals_json
            : $this->recalculateTotals($checkout, $this->currentDiscount($checkout));

        return [
            'id' => (int) $checkout->id,
            'store_id' => (int) $checkout->store_id,
            'cart_id' => (int) $checkout->cart_id,
            'customer_id' => $checkout->customer_id === null ? null : (int) $checkout->customer_id,
            'status' => (string) $this->enumValue($checkout->status),
            'payment_method' => $checkout->payment_method === null ? null : (string) $this->enumValue($checkout->payment_method),
            'email' => $checkout->email,
            'shipping_address_json' => is_array($checkout->shipping_address_json) ? $checkout->shipping_address_json : null,
            'billing_address_json' => is_array($checkout->billing_address_json) ? $checkout->billing_address_json : null,
            'shipping_method_id' => $checkout->shipping_method_id === null ? null : (int) $checkout->shipping_method_id,
            'discount_code' => $checkout->discount_code,
            'lines' => $lines,
            'totals' => [
                'subtotal' => (int) ($totals['subtotal'] ?? 0),
                'discount' => (int) ($totals['discount'] ?? 0),
                'shipping' => (int) ($totals['shipping'] ?? 0),
                'tax' => (int) ($totals['tax'] ?? 0),
                'total' => (int) ($totals['total'] ?? 0),
                'currency' => (string) ($totals['currency'] ?? ($cart?->currency ?? 'USD')),
            ],
            'available_shipping_methods' => $shippingMethods,
            'applied_discounts' => $appliedDiscounts,
            'expires_at' => $this->iso($checkout->expires_at),
            'created_at' => $this->iso($checkout->created_at),
            'updated_at' => $this->iso($checkout->updated_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentResponsePayload(Checkout $checkout, Order $order): array
    {
        $isBankTransfer = (string) $this->enumValue($order->payment_method) === 'bank_transfer';

        $payload = [
            'checkout_id' => (int) $checkout->id,
            'status' => 'completed',
            'order' => [
                'id' => (int) $order->id,
                'order_number' => (string) $order->order_number,
                'status' => (string) $this->enumValue($order->status),
                'financial_status' => (string) $this->enumValue($order->financial_status),
                'payment_method' => (string) $this->enumValue($order->payment_method),
                'total_amount' => (int) $order->total_amount,
                'currency' => (string) $order->currency,
            ],
        ];

        if ($isBankTransfer) {
            $payload['bank_transfer_instructions'] = [
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'reference' => (string) $order->order_number,
                'amount_formatted' => number_format(((int) $order->total_amount) / 100, 2).' '.(string) $order->currency,
            ];
        }

        return $payload;
    }
}
