<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\UnserviceableShippingAddressException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\V1\ApplyCheckoutDiscountRequest;
use App\Http\Requests\Api\Storefront\V1\CompleteCheckoutPaymentRequest;
use App\Http\Requests\Api\Storefront\V1\SelectCheckoutPaymentRequest;
use App\Http\Requests\Api\Storefront\V1\SetCheckoutAddressRequest;
use App\Http\Requests\Api\Storefront\V1\SetCheckoutShippingRequest;
use App\Http\Requests\Api\Storefront\V1\StoreCheckoutRequest;
use App\Http\Resources\Storefront\V1\CheckoutResource;
use App\Http\Resources\Storefront\V1\OrderResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Services\CheckoutService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use App\Support\CheckoutAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CheckoutController extends Controller
{
    public function store(StoreCheckoutRequest $request, CheckoutService $checkouts, PricingEngine $pricing): CheckoutResource|JsonResponse
    {
        $cart = Cart::query()->findOrFail($request->validated('cart_id'));
        abort_unless((int) $cart->store_id === $this->currentStore()->getKey(), 404);

        try {
            $checkout = $checkouts->createFromCart($cart);
            $checkout->forceFill([
                'email' => (string) $request->validated('email'),
            ])->save();
            $pricing->calculate($checkout);

            return CheckoutResource::make($this->loadCheckout(
                $checkout->refresh(),
            ))->response()->setStatusCode(201);
        } catch (InvalidCheckoutTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function show(Request $request, Checkout $checkout): CheckoutResource
    {
        $this->authorizeCheckoutAccess($request, $checkout);

        return CheckoutResource::make($this->loadCheckout($checkout));
    }

    public function address(SetCheckoutAddressRequest $request, Checkout $checkout, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        $this->authorizeCheckoutAccess($request, $checkout);

        $addressData = $request->validated();
        $addressData['email'] ??= $checkout->email;

        try {
            return CheckoutResource::make($this->loadCheckout($checkouts->setAddress($checkout, $addressData)));
        } catch (InvalidCheckoutTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function shippingMethod(SetCheckoutShippingRequest $request, Checkout $checkout, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        $this->authorizeCheckoutAccess($request, $checkout);

        try {
            return CheckoutResource::make($this->loadCheckout($checkouts->setShippingMethod(
                $checkout,
                $request->validated('shipping_rate_id'),
            )));
        } catch (InvalidCheckoutTransitionException|UnserviceableShippingAddressException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function applyDiscount(ApplyCheckoutDiscountRequest $request, Checkout $checkout, PricingEngine $pricing): CheckoutResource|JsonResponse
    {
        $this->authorizeCheckoutAccess($request, $checkout);

        $checkout->forceFill([
            'discount_code' => trim((string) $request->validated('code')) ?: null,
        ])->save();

        try {
            $pricing->calculate($checkout);

            return CheckoutResource::make($this->loadCheckout($checkout->refresh()));
        } catch (InvalidDiscountException $exception) {
            $checkout->forceFill(['discount_code' => null])->save();
            $pricing->calculate($checkout);

            return response()->json([
                'message' => $exception->getMessage(),
                'reason' => $exception->reasonCode,
            ], 422);
        }
    }

    public function destroyDiscount(Request $request, Checkout $checkout, PricingEngine $pricing): CheckoutResource
    {
        $this->authorizeCheckoutAccess($request, $checkout);
        abort_if($checkout->discount_code === null, 404);

        $checkout->forceFill(['discount_code' => null])->save();
        $pricing->calculate($checkout);

        return CheckoutResource::make($this->loadCheckout($checkout->refresh()));
    }

    public function paymentMethod(SelectCheckoutPaymentRequest $request, Checkout $checkout, CheckoutService $checkouts): CheckoutResource|JsonResponse
    {
        $this->authorizeCheckoutAccess($request, $checkout);

        try {
            return CheckoutResource::make($this->loadCheckout($checkouts->selectPaymentMethod(
                $checkout,
                (string) $request->validated('payment_method'),
            )));
        } catch (InsufficientInventoryException|InvalidCheckoutTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function pay(CompleteCheckoutPaymentRequest $request, Checkout $checkout, CheckoutService $checkouts): OrderResource|JsonResponse
    {
        $this->authorizeCheckoutAccess($request, $checkout);

        try {
            if ($checkout->payment_method !== $request->validated('payment_method')) {
                $checkout = $checkouts->selectPaymentMethod($checkout, (string) $request->validated('payment_method'));
            }

            $order = $checkouts->completeCheckout($checkout, [
                'card_number' => $request->validated('card_number'),
                'cardholder_name' => $request->validated('card_holder'),
                'expiry' => $request->validated('card_expiry'),
                'cvc' => $request->validated('card_cvc'),
            ]);

            return OrderResource::make($this->loadOrder($order))
                ->response()
                ->setStatusCode(200);
        } catch (PaymentFailedException $exception) {
            return response()->json(['message' => $exception->getMessage()], 402);
        } catch (InsufficientInventoryException|InvalidCheckoutTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    private function authorizeCheckoutAccess(Request $request, Checkout $checkout): void
    {
        abort_unless((int) $checkout->store_id === $this->currentStore()->getKey(), 404);
        abort_unless(CheckoutAccessToken::valid($checkout, $this->tokenFromRequest($request)), 404);
    }

    private function tokenFromRequest(Request $request): ?string
    {
        $token = $request->query('token');

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = $request->header('X-Checkout-Token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function currentStore(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function loadCheckout(Checkout $checkout): Checkout
    {
        $checkout = $checkout->load([
            'cart.lines.variant.product',
            'cart.lines.variant.optionValues.option',
            'store',
        ]);

        $checkout->setRelation('availableRates', $this->availableRates($checkout));

        return $checkout;
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load(['lines', 'payments', 'fulfillments.lines']);
    }

    /**
     * @return Collection<int, ShippingRate>
     */
    private function availableRates(Checkout $checkout): Collection
    {
        if (! is_array($checkout->shipping_address_json) || $checkout->shipping_address_json === []) {
            return collect();
        }

        return app(ShippingCalculator::class)
            ->getAvailableRates($checkout->store, $checkout->shipping_address_json)
            ->map(function (ShippingRate $rate) use ($checkout): ShippingRate {
                $rate->setAttribute('calculated_amount', app(ShippingCalculator::class)->calculate($rate, $checkout->cart));

                return $rate;
            });
    }
}
