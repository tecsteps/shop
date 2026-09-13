<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\ApplyDiscountRequest;
use App\Http\Requests\Api\Storefront\CreateCheckoutRequest;
use App\Http\Requests\Api\Storefront\PayCheckoutRequest;
use App\Http\Requests\Api\Storefront\SelectPaymentMethodRequest;
use App\Http\Requests\Api\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Api\Storefront\SetShippingMethodRequest;
use App\Http\Resources\Api\Storefront\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Support\OrderAccessToken;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkouts,
        protected DiscountService $discounts,
        protected CurrentStore $currentStore,
    ) {}

    public function store(CreateCheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cart = Cart::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($validated['cart_id'])
            ->first();

        if ($cart === null) {
            abort(404);
        }

        $checkout = $this->checkouts->createFromCart($cart, $validated['email']);

        return response()->json((new CheckoutResource($this->loadCheckout($checkout)))->resolve(), 201);
    }

    public function show(int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        return response()->json((new CheckoutResource($checkout))->resolve());
    }

    public function setAddress(SetCheckoutAddressRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);
        $validated = $request->validated();

        try {
            $checkout = $this->checkouts->setAddress(
                $checkout,
                $validated['shipping_address'],
                $validated['billing_address'] ?? null,
                (bool) ($validated['use_shipping_as_billing'] ?? true),
                $validated['email'] ?? null,
            );
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['status' => [$exception->getMessage()]]);
        }

        return response()->json((new CheckoutResource($this->loadCheckout($checkout)))->resolve());
    }

    public function setShippingMethod(SetShippingMethodRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        try {
            $checkout = $this->checkouts->setShippingMethod($checkout, $request->validated('shipping_method_id'));
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['status' => [$exception->getMessage()]]);
        }

        return response()->json((new CheckoutResource($this->loadCheckout($checkout)))->resolve());
    }

    public function selectPaymentMethod(SelectPaymentMethodRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        try {
            $checkout = $this->checkouts->selectPaymentMethod($checkout, PaymentMethod::from($request->validated('payment_method')));
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['status' => [$exception->getMessage()]]);
        }

        return response()->json((new CheckoutResource($this->loadCheckout($checkout)))->resolve());
    }

    public function applyDiscount(ApplyDiscountRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        try {
            $checkout = $this->checkouts->applyDiscount($checkout, $request->validated('code'), $this->discounts);
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['status' => [$exception->getMessage()]]);
        }

        return response()->json((new CheckoutResource($this->loadCheckout($checkout)))->resolve());
    }

    public function removeDiscount(int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        if ($checkout->discount_code === null) {
            abort(404);
        }

        try {
            $checkout = $this->checkouts->removeDiscount($checkout);
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['status' => [$exception->getMessage()]]);
        }

        return response()->json((new CheckoutResource($this->loadCheckout($checkout)))->resolve());
    }

    public function pay(PayCheckoutRequest $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);
        $validated = $request->validated();

        if (! in_array($checkout->status, [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected], true)) {
            return response()->json([
                'message' => 'This checkout is not ready for payment.',
            ], 409);
        }

        try {
            $order = $this->checkouts->pay($checkout, PaymentMethod::from($validated['payment_method']), $validated);
        } catch (PaymentFailedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], 422);
        } catch (InvalidCheckoutTransitionException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

        $payload = [
            'checkout_id' => $checkout->getKey(),
            'status' => CheckoutStatus::Completed->value,
            'order' => [
                'id' => $order->getKey(),
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'financial_status' => $order->financial_status->value,
                'payment_method' => $order->payment_method->value,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'access_token' => OrderAccessToken::generate($order),
            ],
        ];

        if ($order->payment_method === PaymentMethod::BankTransfer) {
            $payload['bank_transfer_instructions'] = [
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'reference' => $order->order_number,
                'amount_formatted' => number_format($order->total_amount / 100, 2).' '.$order->currency,
            ];
        }

        return response()->json($payload);
    }

    protected function findCheckout(int $checkoutId): Checkout
    {
        $checkout = Checkout::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($checkoutId)
            ->first();

        if ($checkout === null) {
            abort(404);
        }

        if ($checkout->status === CheckoutStatus::Expired) {
            abort(410);
        }

        return $this->loadCheckout($checkout);
    }

    protected function loadCheckout(Checkout $checkout): Checkout
    {
        return $checkout->load([
            'cart.cartLines.variant.product',
            'cart.cartLines.variant.optionValues',
        ]);
    }
}
