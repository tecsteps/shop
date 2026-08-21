<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidDiscountException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyDiscountRequest;
use App\Http\Requests\SetCheckoutAddressRequest;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PaymentService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontCheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkouts, private readonly PricingEngine $pricing, private readonly ShippingCalculator $shipping, private readonly DiscountService $discounts, private readonly PaymentService $payments) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['cart_id' => ['required', 'integer'], 'email' => ['required', 'email']]);
        $cart = Cart::query()->with('lines')->findOrFail($data['cart_id']);
        $customerId = $request->user('customer')?->getKey();
        if ($customerId !== null) {
            abort_unless((int) $cart->customer_id === (int) $customerId, 404);
        } else {
            $sessionCartIds = array_filter([$request->session()->get('cart_id'), $request->session()->get('cart_id_'.app('current_store')->getKey())]);
            abort_unless(in_array($cart->getKey(), $sessionCartIds, true), 404);
        }
        $checkout = $this->checkouts->create($cart, $data['email'], $request->user('customer'));
        $this->pricing->calculate($checkout);

        return response()->json($this->payload($checkout->refresh()), 201);
    }

    public function show(int $checkoutId): JsonResponse
    {
        $checkout = $this->checkout($checkoutId);

        if ($checkout->isExpired()) {
            return response()->json(['message' => 'Checkout expired.'], 410);
        }

        return response()->json($this->payload($checkout));
    }

    public function address(SetCheckoutAddressRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->checkout($checkoutId);
        $useShippingAsBilling = $request->boolean('use_shipping_as_billing', true);
        $data = $request->validated();
        try {
            $checkout = $this->checkouts->setAddress($checkout, $data['shipping_address'] ?? [], $data['billing_address'] ?? null, $useShippingAsBilling);
        } catch (\LogicException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'checkout_state_invalid'], 422);
        }

        return response()->json($this->payload($checkout));
    }

    public function shippingMethod(Request $request, int $checkoutId): JsonResponse
    {
        $data = $request->validate(['shipping_method_id' => ['required', 'integer'], 'shipping_rate_id' => ['nullable', 'integer']]);
        try {
            $checkout = $this->checkouts->setShippingMethod($this->checkout($checkoutId), $data['shipping_rate_id'] ?? $data['shipping_method_id']);
        } catch (\LogicException|\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'checkout_state_invalid'], 422);
        }

        return response()->json($this->payload($checkout));
    }

    public function applyDiscount(ApplyDiscountRequest $request, int $checkoutId): JsonResponse
    {
        $data = $request->validated();
        $checkout = $this->checkout($checkoutId);
        try {
            $discount = $this->discounts->validate($data['code'], app('current_store'), $checkout->cart);
        } catch (InvalidDiscountException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => $exception->reason], 422);
        }
        $checkout->update(['discount_code' => $discount->code]);
        $this->pricing->calculate($checkout->refresh());

        return response()->json($this->payload($checkout->refresh()));
    }

    public function removeDiscount(int $checkoutId): JsonResponse
    {
        $checkout = $this->checkout($checkoutId);
        $checkout->update(['discount_code' => null]);
        $this->pricing->calculate($checkout->refresh());

        return response()->json($this->payload($checkout->refresh()));
    }

    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'regex:/^(?=.*\d)[0-9 ]+$/'],
            'card_expiry' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'card_cvc' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'regex:/^\d{3,4}$/'],
            'card_holder' => ['exclude_unless:payment_method,credit_card', 'required', 'string', 'max:255'],
        ]);
        try {
            $checkout = $this->checkouts->selectPaymentMethod($this->checkout($checkoutId), $data['payment_method']);
            $order = $this->payments->pay($checkout, PaymentMethod::from($data['payment_method']), $data);
        } catch (InsufficientInventoryException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'insufficient_inventory'], 422);
        } catch (\LogicException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'checkout_state_invalid'], 422);
        }

        if ($order === null) {
            return response()->json(['message' => 'Payment failed.', 'code' => 'payment_failed'], 422);
        }

        return response()->json(['order' => ['id' => $order->id, 'order_number' => $order->order_number, 'status' => $order->status, 'financial_status' => $order->financial_status, 'total_amount' => $order->total_amount], 'message' => $order->financial_status->value === 'pending' ? 'Bank transfer instructions generated.' : 'Order confirmed.']);
    }

    public function paymentMethod(Request $request, int $checkoutId): JsonResponse
    {
        $data = $request->validate(['payment_method' => ['required', 'in:credit_card,paypal,bank_transfer']]);
        try {
            $checkout = $this->checkouts->selectPaymentMethod($this->checkout($checkoutId), $data['payment_method']);
        } catch (InsufficientInventoryException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'insufficient_inventory'], 422);
        } catch (\LogicException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'code' => 'checkout_state_invalid'], 422);
        }

        return response()->json($this->payload($checkout));
    }

    private function checkout(int $checkoutId): Checkout
    {
        $checkout = Checkout::query()->with(['cart.lines.variant.product', 'shippingRate'])->findOrFail($checkoutId);
        $customerId = request()->user('customer')?->getKey();

        if ($customerId !== null) {
            abort_unless((int) $checkout->customer_id === (int) $customerId, 404);
        } else {
            $sessionCartIds = array_filter([request()->session()->get('cart_id'), request()->session()->get('cart_id_'.app('current_store')->getKey())]);
            abort_unless(in_array($checkout->cart_id, $sessionCartIds, true), 404);
        }

        return $checkout;
    }

    /** @return array<string, mixed> */
    private function payload(Checkout $checkout): array
    {
        $totals = $checkout->totals_json ?? $this->pricing->calculate($checkout)->toArray();
        $rates = $checkout->shipping_address_json === null ? collect() : $this->shipping->getAvailableRates(app('current_store'), $checkout->shipping_address_json);

        return ['id' => $checkout->id, 'store_id' => $checkout->store_id, 'cart_id' => $checkout->cart_id, 'customer_id' => $checkout->customer_id, 'status' => $checkout->status, 'email' => $checkout->email, 'payment_method' => $checkout->payment_method, 'shipping_address_json' => $checkout->shipping_address_json, 'billing_address_json' => $checkout->billing_address_json, 'shipping_method_id' => $checkout->shipping_rate_id, 'discount_code' => $checkout->discount_code, 'lines' => $checkout->cart->lines->map(fn ($line): array => ['variant_id' => $line->variant_id, 'product_title' => $line->variant->product->title, 'variant_title' => $line->variant->title, 'sku' => $line->variant->sku, 'quantity' => $line->quantity, 'unit_price_amount' => $line->unit_price_amount, 'line_total_amount' => $line->line_total_amount])->all(), 'totals' => $totals, 'available_shipping_methods' => $rates->map(fn ($rate): array => ['id' => $rate->id, 'name' => $rate->name, 'type' => $rate->type, 'price_amount' => $rate->price_amount, 'currency' => $rate->currency, 'estimated_days_min' => $rate->estimated_days_min, 'estimated_days_max' => $rate->estimated_days_max])->all(), 'expires_at' => $checkout->expires_at, 'created_at' => $checkout->created_at];
    }
}
