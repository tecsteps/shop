<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyDiscountRequest;
use App\Http\Requests\Storefront\CreateCheckoutRequest;
use App\Http\Requests\Storefront\SelectPaymentMethodRequest;
use App\Http\Requests\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Storefront\SetShippingMethodRequest;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected DiscountService $discountService,
        protected ShippingCalculator $shippingCalculator,
        protected PricingEngine $pricingEngine
    ) {}

    public function store(CreateCheckoutRequest $request): JsonResponse
    {
        $store = app('current_store');

        $cart = Cart::query()
            ->withoutGlobalScopes()
            ->where('id', $request->integer('cart_id'))
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active)
            ->firstOrFail();

        if ($cart->lines()->count() === 0) {
            return response()->json(['message' => 'Cart is empty.', 'errors' => ['cart_id' => ['Cart must have at least one line.']]], 422);
        }

        $checkout = $this->checkoutService->createFromCart($cart);
        $checkout->update(['email' => $request->input('email')]);

        return response()->json($this->formatCheckout($checkout->fresh()), 201);
    }

    public function show(int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        return response()->json($this->formatCheckout($checkout));
    }

    public function setAddress(SetCheckoutAddressRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        try {
            $addressData = [
                'email' => $checkout->email,
                'shipping_address' => $request->input('shipping_address'),
                'billing_address' => $request->boolean('use_shipping_as_billing', true)
                    ? null
                    : $request->input('billing_address'),
            ];

            $checkout = $this->checkoutService->setAddress($checkout, $addressData);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatCheckout($checkout));
    }

    public function setShippingMethod(SetShippingMethodRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        try {
            $checkout = $this->checkoutService->setShippingMethod(
                $checkout,
                $request->integer('shipping_method_id')
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatCheckout($checkout));
    }

    public function selectPaymentMethod(SelectPaymentMethodRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        try {
            $checkout = $this->checkoutService->selectPaymentMethod(
                $checkout,
                $request->input('payment_method')
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->formatCheckout($checkout));
    }

    public function applyDiscount(ApplyDiscountRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);
        $store = app('current_store');
        $cart = $checkout->cart()->with('lines')->first();

        $result = $this->discountService->validate($request->input('code'), $store, $cart);

        if (! $result->valid) {
            return response()->json([
                'message' => $result->errorMessage,
                'error_code' => $result->errorCode,
            ], 422);
        }

        $checkout->update(['discount_code' => $request->input('code')]);
        $this->pricingEngine->calculate($checkout->fresh());

        return response()->json($this->formatCheckout($checkout->fresh()));
    }

    public function removeDiscount(int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        if (! $checkout->discount_code) {
            return response()->json(['message' => 'No discount applied.'], 404);
        }

        $checkout->update(['discount_code' => null]);

        $cart = $checkout->cart()->with('lines')->first();
        foreach ($cart->lines as $line) {
            $line->update([
                'line_discount_amount' => 0,
                'line_total_amount' => $line->line_subtotal_amount,
            ]);
        }

        $this->pricingEngine->calculate($checkout->fresh());

        return response()->json($this->formatCheckout($checkout->fresh()));
    }

    protected function findCheckout(int $checkoutId): Checkout
    {
        return Checkout::query()
            ->withoutGlobalScopes()
            ->where('id', $checkoutId)
            ->where('store_id', app('current_store')->id)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatCheckout(Checkout $checkout): array
    {
        $cart = $checkout->cart()->with('lines.variant.product')->first();
        $store = app('current_store');

        $lines = $cart ? $cart->lines->map(function ($line) {
            $variant = $line->variant;
            $product = $variant?->product;

            return [
                'variant_id' => $line->variant_id,
                'product_title' => $product?->title,
                'variant_title' => $variant?->title ?? null,
                'sku' => $variant?->sku ?? null,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ];
        }) : collect();

        $availableShippingMethods = [];
        if ($checkout->shipping_address_json) {
            $rates = $this->shippingCalculator->getAvailableRates($store, $checkout->shipping_address_json);
            $availableShippingMethods = $rates->map(fn ($rate) => [
                'id' => $rate->id,
                'name' => $rate->name,
                'type' => $rate->type,
                'price_amount' => $rate->amount,
                'currency' => $cart?->currency ?? $store->default_currency,
            ])->values()->toArray();
        }

        $totals = $checkout->totals_json ?? [
            'subtotal' => $lines->sum('line_total_amount'),
            'discount' => 0,
            'shipping' => 0,
            'tax_total' => 0,
            'total' => $lines->sum('line_total_amount'),
            'currency' => $cart?->currency ?? $store->default_currency,
        ];

        return [
            'id' => $checkout->id,
            'store_id' => $checkout->store_id,
            'cart_id' => $checkout->cart_id,
            'customer_id' => $checkout->customer_id,
            'status' => $checkout->status->value,
            'email' => $checkout->email,
            'payment_method' => $checkout->payment_method,
            'shipping_address_json' => $checkout->shipping_address_json,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->shipping_method_id,
            'discount_code' => $checkout->discount_code,
            'lines' => $lines,
            'totals' => $totals,
            'available_shipping_methods' => $availableShippingMethods,
            'expires_at' => $checkout->expires_at?->toIso8601String(),
            'created_at' => $checkout->created_at?->toIso8601String(),
        ];
    }
}
