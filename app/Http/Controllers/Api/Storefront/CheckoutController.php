<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\PaymentMethod;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\UnavailableShippingRateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyDiscountRequest;
use App\Http\Requests\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Storefront\SetCheckoutPaymentMethodRequest;
use App\Http\Requests\Storefront\SetCheckoutShippingMethodRequest;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Http\Resources\Storefront\CheckoutResource;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function store(StoreCheckoutRequest $request, CartService $carts, CheckoutService $checkouts): JsonResponse
    {
        $cart = $carts->findForStore(app('current_store'), (int) $request->validated('cart_id'));
        $checkout = $checkouts->createFromCart($cart, (string) $request->validated('email'));

        return (new CheckoutResource($checkout))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $checkoutId, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        $checkout = $checkouts->findForStore($checkoutId);

        if ($checkout->isExpired()) {
            return response()->json(['message' => 'This checkout has expired.'], 410);
        }

        return new CheckoutResource($checkout);
    }

    public function address(SetCheckoutAddressRequest $request, int $checkoutId, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        try {
            return new CheckoutResource(
                $checkouts->setAddress($checkouts->findForStore($checkoutId), $request->validated()),
            );
        } catch (CheckoutStateException $exception) {
            return $this->checkoutStateError($exception);
        }
    }

    public function shippingMethod(SetCheckoutShippingMethodRequest $request, int $checkoutId, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        try {
            return new CheckoutResource(
                $checkouts->setShippingMethod(
                    $checkouts->findForStore($checkoutId),
                    (int) $request->validated('shipping_method_id'),
                ),
            );
        } catch (UnavailableShippingRateException $exception) {
            throw ValidationException::withMessages([
                'shipping_method_id' => [$exception->getMessage()],
            ]);
        } catch (CheckoutStateException $exception) {
            return $this->checkoutStateError($exception);
        }
    }

    public function paymentMethod(SetCheckoutPaymentMethodRequest $request, int $checkoutId, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        try {
            return new CheckoutResource(
                $checkouts->selectPaymentMethod(
                    $checkouts->findForStore($checkoutId),
                    PaymentMethod::from((string) $request->validated('payment_method')),
                ),
            );
        } catch (CheckoutStateException $exception) {
            return $this->checkoutStateError($exception);
        }
    }

    public function applyDiscount(ApplyDiscountRequest $request, int $checkoutId, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        try {
            return new CheckoutResource(
                $checkouts->applyDiscount($checkouts->findForStore($checkoutId), (string) $request->validated('code')),
            );
        } catch (InvalidDiscountException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], $exception->errorCode === 'discount_not_found' ? 422 : 400);
        } catch (CheckoutStateException $exception) {
            return $this->checkoutStateError($exception);
        }
    }

    public function removeDiscount(int $checkoutId, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        try {
            return new CheckoutResource(
                $checkouts->removeDiscount($checkouts->findForStore($checkoutId)),
            );
        } catch (CheckoutStateException $exception) {
            return $this->checkoutStateError($exception);
        }
    }

    private function checkoutStateError(CheckoutStateException $exception): JsonResponse
    {
        $status = str_contains($exception->getMessage(), 'expired') ? 410 : 422;

        return response()->json(['message' => $exception->getMessage()], $status);
    }
}
