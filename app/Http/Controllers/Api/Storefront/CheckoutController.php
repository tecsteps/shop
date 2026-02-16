<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => 'required|integer|exists:carts,id',
            'email' => 'required|email',
        ]);

        /** @var Store $store */
        $store = app('current_store');
        /** @var Cart $cart */
        $cart = Cart::query()->findOrFail($validated['cart_id']);
        abort_unless($cart->store_id === $store->id, 404);

        $checkout = $this->checkoutService->createFromCart($cart);
        $checkout->update(['email' => $validated['email']]);

        return response()->json($checkout->fresh(), 201);
    }

    public function show(Checkout $checkout): JsonResponse
    {
        $this->verifyStoreOwnership($checkout);

        return response()->json($checkout->load('cart.lines'));
    }

    public function setAddress(Request $request, Checkout $checkout): JsonResponse
    {
        $this->verifyStoreOwnership($checkout);

        $validated = $request->validate([
            'email' => 'sometimes|email',
            'shipping_address' => 'required|array',
            'billing_address' => 'sometimes|array',
        ]);

        $checkout = $this->checkoutService->setAddress($checkout, $validated);

        return response()->json($checkout);
    }

    public function setShippingMethod(Request $request, Checkout $checkout): JsonResponse
    {
        $this->verifyStoreOwnership($checkout);

        $validated = $request->validate([
            'shipping_rate_id' => 'required|integer|exists:shipping_rates,id',
        ]);

        $checkout = $this->checkoutService->setShippingMethod($checkout, $validated['shipping_rate_id']);

        return response()->json($checkout);
    }

    public function applyDiscount(Request $request, Checkout $checkout): JsonResponse
    {
        $this->verifyStoreOwnership($checkout);

        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $discount = Discount::where('store_id', $checkout->store_id)
            ->where('code', $validated['code'])
            ->first();

        if (! $discount) {
            return response()->json(['message' => 'Invalid discount code.'], 422);
        }

        $checkout->update(['discount_code' => $validated['code']]);

        return response()->json($checkout->fresh());
    }

    public function setPaymentMethod(Request $request, Checkout $checkout): JsonResponse
    {
        $this->verifyStoreOwnership($checkout);

        $validated = $request->validate([
            'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer',
        ]);

        $checkout = $this->checkoutService->selectPaymentMethod($checkout, $validated['payment_method']);

        return response()->json($checkout);
    }

    public function pay(Request $request, Checkout $checkout): JsonResponse
    {
        $this->verifyStoreOwnership($checkout);

        $validated = $request->validate([
            'payment_method' => 'sometimes|string|in:credit_card,paypal,bank_transfer',
        ]);

        $checkout = $this->checkoutService->completeCheckout($checkout, $validated);

        return response()->json($checkout);
    }

    private function verifyStoreOwnership(Checkout $checkout): void
    {
        /** @var Store $store */
        $store = app('current_store');
        abort_unless($checkout->store_id === $store->id, 404);
    }
}
