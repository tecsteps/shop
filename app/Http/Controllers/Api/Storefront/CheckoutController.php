<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\UnavailableShippingRateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyDiscountRequest;
use App\Http\Requests\Storefront\PayCheckoutRequest;
use App\Http\Requests\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Storefront\SetCheckoutPaymentMethodRequest;
use App\Http\Requests\Storefront\SetCheckoutShippingMethodRequest;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Http\Resources\Storefront\CheckoutResource;
use App\Http\Resources\Storefront\OrderResource;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
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

    public function pay(PayCheckoutRequest $request, int $checkoutId, CheckoutService $checkouts, OrderService $orders): JsonResponse
    {
        $checkout = $checkouts->findForStore($checkoutId)->load('order.lines', 'order.payments', 'order.fulfillments.lines');

        if ($checkout->order !== null) {
            return $this->orderResponse($checkout->order);
        }

        try {
            $method = PaymentMethod::from((string) $request->validated('payment_method'));

            if ($checkout->status !== CheckoutStatus::PaymentSelected || $checkout->payment_method !== $method) {
                $checkout = $checkouts->selectPaymentMethod($checkout, $method);
            }

            return $this->orderResponse($orders->createFromCheckout($checkout, $request->validated()));
        } catch (PaymentFailedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], 422);
        } catch (CheckoutStateException $exception) {
            $status = str_contains($exception->getMessage(), 'expired') ? 410 : 409;

            return response()->json(['message' => $exception->getMessage()], $status);
        }
    }

    private function checkoutStateError(CheckoutStateException $exception): JsonResponse
    {
        $status = str_contains($exception->getMessage(), 'expired') ? 410 : 422;

        return response()->json(['message' => $exception->getMessage()], $status);
    }

    private function orderResponse(Order $order): JsonResponse
    {
        $payload = [
            'checkout_id' => $order->checkout_id,
            'status' => 'completed',
            'order' => (new OrderResource($order))->resolve(),
        ];

        if ($order->payment_method === PaymentMethod::BankTransfer) {
            $payload['bank_transfer_instructions'] = $this->bankTransferInstructions($order);
        }

        return response()->json($payload);
    }

    /**
     * @return array{bank_name: string, iban: string, bic: string, reference: string, amount_formatted: string}
     */
    private function bankTransferInstructions(Order $order): array
    {
        return [
            'bank_name' => 'Mock Bank AG',
            'iban' => 'DE89 3704 0044 0532 0130 00',
            'bic' => 'COBADEFFXXX',
            'reference' => $order->order_number,
            'amount_formatted' => number_format($order->total_amount / 100, 2).' '.$order->currency,
        ];
    }
}
