<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\ApplyDiscountRequest;
use App\Http\Requests\Api\Storefront\CreateCheckoutRequest;
use App\Http\Requests\Api\Storefront\PayCheckoutRequest;
use App\Http\Requests\Api\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Api\Storefront\SetPaymentMethodRequest;
use App\Http\Requests\Api\Storefront\SetShippingMethodRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private OrderService $orderService,
        private DiscountService $discountService,
    ) {}

    public function store(CreateCheckoutRequest $request): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $cart = Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($request->validated('cart_id'));

        $checkout = $this->checkoutService->createFromCart($cart);
        $checkout->update(['email' => $request->validated('email')]);

        return (new CheckoutResource($checkout->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $checkout): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $checkoutModel = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkout);

        return (new CheckoutResource($checkoutModel))->response();
    }

    public function setAddress(SetCheckoutAddressRequest $request, int $checkout): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $checkoutModel = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkout);

        $shippingAddress = $request->validated('shipping_address');
        $addressData = array_merge($shippingAddress, [
            'email' => $checkoutModel->email,
            'zip' => $shippingAddress['postal_code'] ?? '',
        ]);

        try {
            $checkoutModel = $this->checkoutService->setAddress($checkoutModel, $addressData);
        } catch (InvalidCheckoutTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CheckoutResource($checkoutModel))->response();
    }

    public function setShippingMethod(SetShippingMethodRequest $request, int $checkout): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $checkoutModel = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkout);

        try {
            $checkoutModel = $this->checkoutService->setShippingMethod(
                $checkoutModel,
                (int) $request->validated('shipping_method_id'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (InvalidCheckoutTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CheckoutResource($checkoutModel))->response();
    }

    public function applyDiscount(ApplyDiscountRequest $request, int $checkout): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $checkoutModel = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkout);

        $code = $request->validated('code');

        try {
            $checkoutModel->loadMissing('cart');
            $this->discountService->validate($code, $store, $checkoutModel->cart);
            $checkoutModel = $this->checkoutService->applyDiscount($checkoutModel, $code);
        } catch (\App\Exceptions\InvalidDiscountException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CheckoutResource($checkoutModel))->response();
    }

    public function setPaymentMethod(SetPaymentMethodRequest $request, int $checkout): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $checkoutModel = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkout);

        try {
            $checkoutModel = $this->checkoutService->selectPaymentMethod(
                $checkoutModel,
                $request->validated('payment_method'),
            );
        } catch (InvalidCheckoutTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new CheckoutResource($checkoutModel))->response();
    }

    public function pay(PayCheckoutRequest $request, int $checkout): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $checkoutModel = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkout);

        if ($checkoutModel->status !== \App\Enums\CheckoutStatus::PaymentSelected) {
            return response()->json([
                'message' => 'Checkout is not in a valid state for payment.',
            ], 409);
        }

        $paymentData = $request->validated();

        try {
            $order = $this->orderService->createFromCheckout($checkoutModel, $paymentData);
        } catch (PaymentFailedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], 422);
        }

        return response()->json([
            'checkout_id' => $checkoutModel->id,
            'status' => 'completed',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'financial_status' => $order->financial_status->value,
                'payment_method' => $order->payment_method->value,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
            ],
        ]);
    }
}
