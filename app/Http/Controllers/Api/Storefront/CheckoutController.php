<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly ShippingCalculator $shippingCalculator,
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
        ]);

        $cart = Cart::findOrFail($validated['cart_id']);

        try {
            $checkout = $this->checkoutService->create($cart, $validated['email']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['cart_id' => [$e->getMessage()]]);
        }

        return response()->json($this->payload($checkout), 201);
    }

    public function show(Checkout $checkout)
    {
        return response()->json($this->payload($checkout));
    }

    public function setAddress(Request $request, Checkout $checkout)
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'email'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.address1' => ['required', 'string', 'max:500'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.country_code' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
        ]);

        try {
            $checkout = $this->checkoutService->setAddress($checkout, [
                'email' => $validated['email'] ?? $checkout->email,
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'] ?? $validated['shipping_address'],
            ]);
        } catch (InvalidCheckoutTransitionException $e) {
            throw ValidationException::withMessages(['checkout' => [$e->getMessage()]]);
        }

        return response()->json($this->payload($checkout));
    }

    public function setShippingMethod(Request $request, Checkout $checkout)
    {
        $validated = $request->validate(['shipping_method_id' => ['required', 'integer']]);

        try {
            $checkout = $this->checkoutService->setShippingMethod($checkout, $validated['shipping_method_id']);
        } catch (InvalidArgumentException|InvalidCheckoutTransitionException $e) {
            throw ValidationException::withMessages(['shipping_method_id' => [$e->getMessage()]]);
        }

        return response()->json($this->payload($checkout));
    }

    public function selectPaymentMethod(Request $request, Checkout $checkout)
    {
        $validated = $request->validate(['payment_method' => ['required', 'in:credit_card,paypal,bank_transfer']]);

        try {
            $checkout = $this->checkoutService->selectPaymentMethod($checkout, $validated['payment_method']);
        } catch (InvalidCheckoutTransitionException $e) {
            throw ValidationException::withMessages(['payment_method' => [$e->getMessage()]]);
        }

        return response()->json($this->payload($checkout));
    }

    public function applyDiscount(Request $request, Checkout $checkout)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:50']]);

        try {
            $checkout->update(['discount_code' => $validated['code']]);
            $checkout->update(['totals_json' => app(\App\Services\PricingEngine::class)->calculate($checkout)->toArray()]);
        } catch (\App\Exceptions\InvalidDiscountException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => $e->reasonCode], 400);
        }

        return response()->json($this->payload($checkout));
    }

    public function removeDiscount(Request $request, Checkout $checkout)
    {
        $checkout->update(['discount_code' => null]);
        $checkout->update(['totals_json' => app(\App\Services\PricingEngine::class)->calculate($checkout)->toArray()]);

        return response()->json($this->payload($checkout));
    }

    public function pay(Request $request, Checkout $checkout)
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card'],
            'card_expiry' => ['required_if:payment_method,credit_card'],
            'card_cvc' => ['required_if:payment_method,credit_card'],
            'card_holder' => ['required_if:payment_method,credit_card'],
        ]);

        try {
            $order = $this->checkoutService->completeCheckout($checkout, $validated);
        } catch (PaymentFailedException $e) {
            return response()->json(['message' => $e->getMessage(), 'error_code' => $e->errorCode], 422);
        } catch (InvalidCheckoutTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'checkout_id' => $checkout->id,
            'status' => 'completed',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'financial_status' => $order->financial_status,
                'payment_method' => $order->payment_method,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Checkout $checkout): array
    {
        $totals = $checkout->totals_json ?? [];

        return [
            'id' => $checkout->id,
            'store_id' => $checkout->store_id,
            'cart_id' => $checkout->cart_id,
            'customer_id' => $checkout->customer_id,
            'status' => $checkout->status,
            'email' => $checkout->email,
            'shipping_address_json' => $checkout->shipping_address_json,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->shipping_method_id,
            'payment_method' => $checkout->payment_method,
            'discount_code' => $checkout->discount_code,
            'totals' => $totals,
            'available_shipping_methods' => $checkout->shipping_address_json
                ? $this->shippingCalculator->getAvailableRates($checkout->store, $checkout->shipping_address_json, $checkout->cart)->map->toArray()->values()->all()
                : [],
            'expires_at' => $checkout->expires_at?->toISOString(),
        ];
    }
}
