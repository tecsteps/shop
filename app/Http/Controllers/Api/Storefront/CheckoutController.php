<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetCheckoutAddressRequest;
use App\Http\Requests\SetCheckoutShippingRequest;
use App\Http\Resources\CheckoutResource;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkoutService, private readonly PricingEngine $pricingEngine, private readonly DiscountService $discountService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['cart_id' => ['required', 'integer', 'exists:carts,id']]);
        $cart = Cart::query()->findOrFail($validated['cart_id']);
        abort_unless($cart->store_id === app('current_store')->id, Response::HTTP_NOT_FOUND);

        return (new CheckoutResource($this->checkoutService->create($cart)))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Checkout $checkout): CheckoutResource
    {
        $this->ensureCurrentStore($checkout);

        return new CheckoutResource($checkout);
    }

    public function address(SetCheckoutAddressRequest $request, Checkout $checkout): CheckoutResource
    {
        $this->ensureCurrentStore($checkout);

        return new CheckoutResource($this->checkoutService->setAddress($checkout, $request->validated()));
    }

    public function shipping(SetCheckoutShippingRequest $request, Checkout $checkout): CheckoutResource
    {
        $this->ensureCurrentStore($checkout);

        return new CheckoutResource($this->checkoutService->setShippingMethod($checkout, $request->validated('shipping_rate_id')));
    }

    public function paymentMethod(Request $request, Checkout $checkout): CheckoutResource
    {
        $this->ensureCurrentStore($checkout);
        $validated = $request->validate(['payment_method' => ['required', 'in:credit_card,paypal,bank_transfer']]);

        return new CheckoutResource($this->checkoutService->selectPaymentMethod($checkout, PaymentMethod::from($validated['payment_method'])));
    }

    public function applyDiscount(Request $request, Checkout $checkout): CheckoutResource
    {
        $this->ensureCurrentStore($checkout);
        $validated = $request->validate(['code' => ['required', 'string', 'max:255']]);
        $this->discountService->validate($validated['code'], $checkout->store, $checkout->cart);
        $checkout->update(['discount_code' => $validated['code']]);
        $this->pricingEngine->calculate($checkout->refresh());

        return new CheckoutResource($checkout->refresh());
    }

    public function removeDiscount(Checkout $checkout): CheckoutResource
    {
        $this->ensureCurrentStore($checkout);
        $checkout->update(['discount_code' => null]);
        $this->pricingEngine->calculate($checkout->refresh());

        return new CheckoutResource($checkout->refresh());
    }

    public function pay(Request $request, Checkout $checkout): OrderResource
    {
        $this->ensureCurrentStore($checkout);

        return new OrderResource($this->checkoutService->completeCheckout($checkout, $request->all()));
    }

    private function ensureCurrentStore(Checkout $checkout): void
    {
        abort_unless($checkout->store_id === app('current_store')->id, Response::HTTP_NOT_FOUND);
    }
}
