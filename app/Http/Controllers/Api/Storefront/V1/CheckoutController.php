<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidDiscountException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApplyDiscountRequest;
use App\Http\Requests\Api\CreateCheckoutRequest;
use App\Http\Requests\Api\PayRequest;
use App\Http\Requests\Api\SetAddressRequest;
use App\Http\Requests\Api\SetShippingMethodRequest;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private PricingEngine $pricingEngine,
        private DiscountService $discountService,
        private ShippingCalculator $shippingCalculator,
    ) {}

    public function store(CreateCheckoutRequest $request): JsonResponse
    {
        $store = app('current_store');
        $cart = Cart::where('store_id', $store->id)->findOrFail($request->integer('cart_id'));

        if ($cart->status !== CartStatus::Active) {
            return response()->json([
                'message' => 'Cart is not active.',
            ], 422);
        }

        if ($cart->lines()->count() === 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['cart_id' => ['Cart must have at least one line item.']],
            ], 422);
        }

        $checkout = $this->checkoutService->createFromCart($cart, $store);
        $checkout->update(['email' => $request->string('email')]);

        return response()->json($this->formatCheckout($checkout->fresh()), 201);
    }

    public function setAddress(SetAddressRequest $request, Checkout $checkout): JsonResponse
    {
        if ($this->isExpired($checkout)) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        $shippingAddress = $request->input('shipping_address');
        $useShippingAsBilling = $request->boolean('use_shipping_as_billing', true);
        $billingAddress = $useShippingAsBilling ? $shippingAddress : $request->input('billing_address', $shippingAddress);

        $checkout = $this->checkoutService->setAddress($checkout, [
            'email' => $checkout->email,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
        ]);

        return response()->json($this->formatCheckout($checkout));
    }

    public function setShippingMethod(SetShippingMethodRequest $request, Checkout $checkout): JsonResponse
    {
        if ($this->isExpired($checkout)) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        try {
            $checkout = $this->checkoutService->setShippingMethod(
                $checkout,
                $request->integer('shipping_method_id'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['shipping_method_id' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json($this->formatCheckout($checkout));
    }

    public function applyDiscount(ApplyDiscountRequest $request, Checkout $checkout): JsonResponse
    {
        if ($this->isExpired($checkout)) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        $cart = $checkout->cart;

        try {
            $this->discountService->validate($request->string('code'), $cart);
        } catch (InvalidDiscountException $e) {
            $statusCode = match ($e->reasonCode) {
                'discount_expired' => 400,
                'discount_usage_limit_reached' => 400,
                default => 422,
            };

            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->reasonCode,
            ], $statusCode);
        }

        $checkout->update(['discount_code' => $request->string('code')]);

        $store = Store::find($checkout->store_id);
        $result = $this->pricingEngine->calculate($cart, $store, $checkout->fresh());
        $checkout->update(['totals_json' => $result->toArray()]);

        return response()->json($this->formatCheckout($checkout->fresh()));
    }

    public function pay(PayRequest $request, Checkout $checkout): JsonResponse
    {
        if ($this->isExpired($checkout)) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        // Select payment method first
        try {
            $checkout = $this->checkoutService->selectPaymentMethod(
                $checkout,
                $request->string('payment_method'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        // Process payment
        $paymentData = $request->only(['card_number', 'card_expiry', 'card_cvc', 'card_holder']);

        if ($request->string('payment_method') === 'credit_card' && isset($paymentData['card_number'])) {
            $paymentData['card_number'] = str_replace(' ', '', $paymentData['card_number']);
        }

        try {
            $order = $this->checkoutService->completeCheckout($checkout->fresh(), $paymentData);
        } catch (\InvalidArgumentException $e) {
            $errorCode = str_contains($e->getMessage(), 'card_declined')
                ? 'card_declined'
                : (str_contains($e->getMessage(), 'insufficient_funds')
                    ? 'insufficient_funds'
                    : 'payment_failed');

            $message = match ($errorCode) {
                'card_declined' => 'Your card was declined.',
                'insufficient_funds' => 'Your card has insufficient funds.',
                default => $e->getMessage(),
            };

            return response()->json([
                'message' => $message,
                'error_code' => $errorCode,
            ], 422);
        }

        $response = [
            'checkout_id' => $checkout->id,
            'status' => 'completed',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'financial_status' => $order->financial_status->value,
                'payment_method' => $order->payment_method?->value ?? $request->string('payment_method'),
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
            ],
        ];

        if ($request->string('payment_method') === 'bank_transfer') {
            $response['bank_transfer_instructions'] = [
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'reference' => $order->order_number,
                'amount_formatted' => number_format($order->total_amount / 100, 2).' '.$order->currency,
            ];
        }

        return response()->json($response);
    }

    private function isExpired(Checkout $checkout): bool
    {
        if ($checkout->status === CheckoutStatus::Expired) {
            return true;
        }

        if ($checkout->expires_at && $checkout->expires_at->isPast()) {
            $this->checkoutService->expireCheckout($checkout);

            return true;
        }

        return false;
    }

    /** @return array<string, mixed> */
    private function formatCheckout(Checkout $checkout): array
    {
        $checkout->loadMissing('cart.lines');
        $cart = $checkout->cart;
        $store = Store::find($checkout->store_id);

        $lines = $cart->lines->map(fn ($line) => [
            'variant_id' => $line->variant_id,
            'product_title' => $line->product_title,
            'variant_title' => $line->variant_title,
            'sku' => $line->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_total_amount' => $line->line_total_amount,
        ]);

        $totals = $checkout->totals_json ?? [
            'subtotal' => $cart->lines->sum('line_subtotal_amount'),
            'discount' => 0,
            'shipping' => 0,
            'tax_total' => 0,
            'total' => $cart->lines->sum('line_total_amount'),
            'currency' => $cart->currency ?? $store->default_currency,
        ];

        // Get available shipping methods if address is set
        $availableShippingMethods = [];

        if ($checkout->shipping_address_json) {
            $zone = $this->shippingCalculator->getMatchingZone(
                $checkout->shipping_address_json,
                $store,
            );

            if ($zone) {
                $rates = $this->shippingCalculator->getAvailableRates($zone, $cart);
                $availableShippingMethods = array_map(fn ($rate) => [
                    'id' => $rate['id'],
                    'name' => $rate['name'],
                    'type' => $rate['type'],
                    'price_amount' => $rate['amount'],
                    'currency' => $cart->currency ?? $store->default_currency,
                ], $rates);
            }
        }

        return [
            'id' => $checkout->id,
            'store_id' => $checkout->store_id,
            'cart_id' => $checkout->cart_id,
            'customer_id' => $checkout->customer_id,
            'status' => $checkout->status->value,
            'email' => $checkout->email,
            'shipping_address_json' => $checkout->shipping_address_json,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->selected_shipping_rate_id,
            'discount_code' => $checkout->discount_code,
            'lines' => $lines->toArray(),
            'totals' => [
                'subtotal' => $totals['subtotal'] ?? 0,
                'discount' => $totals['discount'] ?? 0,
                'shipping' => $totals['shipping'] ?? 0,
                'tax' => $totals['tax_total'] ?? 0,
                'total' => $totals['total'] ?? 0,
                'currency' => $totals['currency'] ?? $cart->currency ?? $store->default_currency,
            ],
            'available_shipping_methods' => $availableShippingMethods,
            'expires_at' => $checkout->expires_at?->toIso8601String(),
            'created_at' => $checkout->created_at?->toIso8601String(),
        ];
    }
}
