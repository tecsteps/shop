<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkoutService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => 'required|integer',
            'email' => 'required|email|max:255',
        ]);

        $store = app('current_store');
        $cart = Cart::where('id', $validated['cart_id'])
            ->where('store_id', $store->id)
            ->where('status', CartStatus::Active)
            ->first();

        if (! $cart) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        if ($cart->lines()->count() === 0) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['cart_id' => ['Cart must have at least one line item.']],
            ], 422);
        }

        $checkout = Checkout::create([
            'store_id' => $store->id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'email' => $validated['email'],
            'expires_at' => now()->addHours(24),
        ]);

        return (new CheckoutResource($checkout))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Checkout $checkout): CheckoutResource|JsonResponse
    {
        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        return new CheckoutResource($checkout);
    }

    public function setAddress(Request $request, Checkout $checkout): CheckoutResource|JsonResponse
    {
        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string|max:255',
            'shipping_address.last_name' => 'required|string|max:255',
            'shipping_address.address1' => 'required|string|max:500',
            'shipping_address.address2' => 'nullable|string|max:500',
            'shipping_address.city' => 'required|string|max:255',
            'shipping_address.province' => 'nullable|string|max:255',
            'shipping_address.country' => 'required|string|max:255',
            'shipping_address.country_code' => 'required|string|max:10',
            'shipping_address.postal_code' => 'required|string|max:20',
            'shipping_address.phone' => 'nullable|string|max:50',
            'billing_address' => 'nullable|array',
        ]);

        try {
            $checkout = $this->checkoutService->setAddress($checkout, [
                'email' => $checkout->email,
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'] ?? null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new CheckoutResource($checkout);
    }

    public function setShippingMethod(Request $request, Checkout $checkout): CheckoutResource|JsonResponse
    {
        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        $validated = $request->validate([
            'shipping_method_id' => 'required|integer',
        ]);

        try {
            $checkout = $this->checkoutService->setShippingMethod(
                $checkout,
                $validated['shipping_method_id'],
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new CheckoutResource($checkout);
    }

    public function selectPaymentMethod(Request $request, Checkout $checkout): CheckoutResource|JsonResponse
    {
        if ($checkout->status === CheckoutStatus::Expired) {
            return response()->json(['message' => 'Checkout has expired.'], 410);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer',
        ]);

        $paymentMethod = PaymentMethod::from($validated['payment_method']);

        try {
            $checkout = $this->checkoutService->selectPaymentMethod($checkout, $paymentMethod);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new CheckoutResource($checkout);
    }
}
