<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\ApplyDiscountRequest;
use App\Http\Requests\Api\Storefront\PayCheckoutRequest;
use App\Http\Requests\Api\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Api\Storefront\SetShippingMethodRequest;
use App\Http\Resources\CheckoutResource;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
        private readonly OrderService $orderService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $store = app('current_store');
        $cartId = (int) $request->input('cart_id');
        $cart = Cart::query()->where('store_id', $store->getKey())->findOrFail($cartId);

        $checkout = $this->checkoutService->start($store, $cart);

        return (new CheckoutResource($checkout))->response()->setStatusCode(201);
    }

    public function show(Checkout $checkout): JsonResponse
    {
        $this->assertStore($checkout);

        return (new CheckoutResource($checkout))->response();
    }

    public function setAddress(SetCheckoutAddressRequest $request, Checkout $checkout): JsonResponse
    {
        $this->assertStore($checkout);

        try {
            $this->checkoutService->setAddress($checkout, $request->validated());
        } catch (InvalidCheckoutStateException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return (new CheckoutResource($checkout->refresh()))->response();
    }

    public function setShippingMethod(SetShippingMethodRequest $request, Checkout $checkout): JsonResponse
    {
        $this->assertStore($checkout);

        try {
            $this->checkoutService->setShippingMethod($checkout, (int) $request->input('shipping_rate_id'));
        } catch (InvalidCheckoutStateException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return (new CheckoutResource($checkout->refresh()))->response();
    }

    public function applyDiscount(ApplyDiscountRequest $request, Checkout $checkout): JsonResponse
    {
        $this->assertStore($checkout);

        try {
            $this->checkoutService->applyDiscount($checkout, (string) $request->input('code'));
        } catch (InvalidDiscountException $e) {
            return response()->json(['error' => $e->reason], 422);
        }

        return (new CheckoutResource($checkout->refresh()))->response();
    }

    public function removeDiscount(Checkout $checkout): JsonResponse
    {
        $this->assertStore($checkout);

        $this->checkoutService->removeDiscount($checkout);

        return (new CheckoutResource($checkout->refresh()))->response();
    }

    public function pay(PayCheckoutRequest $request, Checkout $checkout): JsonResponse
    {
        $this->assertStore($checkout);
        $method = PaymentMethod::from((string) $request->input('payment_method'));

        try {
            $this->checkoutService->selectPaymentMethod($checkout, $method);
        } catch (InvalidCheckoutStateException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        try {
            $result = $this->paymentService->authorize($checkout->refresh(), $method, [
                'card_number' => (string) $request->input('card_number', ''),
            ]);
        } catch (PaymentFailedException $e) {
            return response()->json(['error' => $e->errorCode], 422);
        }

        $order = $this->orderService->createFromCheckout($checkout->refresh());
        $this->paymentService->recordPayment($order, $method, $result);

        return (new OrderResource($order->load('lines')))->response()->setStatusCode(201);
    }

    protected function assertStore(Checkout $checkout): void
    {
        $store = app('current_store');

        if ((int) $checkout->store_id !== (int) $store->getKey()) {
            abort(404);
        }
    }
}
