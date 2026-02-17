<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentDeclinedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\SetCheckoutAddressRequest;
use App\Http\Requests\Api\Storefront\SetCheckoutShippingRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
        private OrderService $orderService,
        private DiscountService $discountService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cart_id' => ['required', 'integer'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $cart = Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('lines')
            ->findOrFail($request->integer('cart_id'));

        if ($cart->lines->isEmpty()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['cart_id' => ['Cart must have at least one line item.']],
            ], 422);
        }

        $checkout = $this->checkoutService->createFromCart($cart);
        $checkout->update(['email' => $request->string('email')]);

        return (new CheckoutResource($checkout->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $checkoutId): CheckoutResource|JsonResponse
    {
        /** @var Store $store */
        $store = app('current_store');

        $checkout = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkoutId);

        return new CheckoutResource($checkout);
    }

    public function setAddress(SetCheckoutAddressRequest $request, int $checkoutId): CheckoutResource|JsonResponse
    {
        /** @var Store $store */
        $store = app('current_store');
        $checkout = Checkout::query()->withoutGlobalScopes()->where('store_id', $store->id)->findOrFail($checkoutId);

        $useShippingAsBilling = $request->boolean('use_shipping_as_billing', true);
        $billingAddress = $useShippingAsBilling
            ? $request->input('shipping_address')
            : ($request->input('billing_address') ?? $request->input('shipping_address'));

        try {
            $checkout = $this->checkoutService->setAddress($checkout, [
                'email' => $request->input('email'),
                'shipping_address' => $request->input('shipping_address'),
                'billing_address' => $billingAddress,
            ]);
        } catch (InvalidCheckoutTransitionException) {
            return response()->json([
                'message' => 'Invalid checkout state transition.',
            ], 422);
        }

        return new CheckoutResource($checkout);
    }

    public function setShippingMethod(SetCheckoutShippingRequest $request, int $checkoutId): CheckoutResource|JsonResponse
    {
        /** @var Store $store */
        $store = app('current_store');
        $checkout = Checkout::query()->withoutGlobalScopes()->where('store_id', $store->id)->findOrFail($checkoutId);

        try {
            $checkout = $this->checkoutService->setShippingMethod(
                $checkout,
                $request->integer('shipping_method_id'),
            );
        } catch (InvalidCheckoutTransitionException) {
            return response()->json([
                'message' => 'Invalid checkout state transition.',
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['shipping_method_id' => [$e->getMessage()]],
            ], 422);
        }

        return new CheckoutResource($checkout);
    }

    public function applyDiscount(Request $request, int $checkoutId): CheckoutResource|JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $checkout = Checkout::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($checkoutId);

        $cart = $checkout->cart()->with('lines')->first();

        try {
            $this->discountService->validate($request->input('code'), $store, $cart);
        } catch (InvalidDiscountException $e) {
            $status = in_array($e->reason, ['expired', 'not_yet_active', 'usage_limit_reached']) ? 400 : 422;

            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'discount_'.$e->reason,
            ], $status);
        }

        $checkout->update(['discount_code' => $request->input('code')]);

        // Recalculate totals
        $pricingEngine = app(\App\Services\PricingEngine::class);
        $result = $pricingEngine->calculate($checkout->fresh());
        $checkout->update(['totals_json' => $result->toArray()]);

        return new CheckoutResource($checkout->fresh());
    }

    public function selectPaymentMethod(Request $request, int $checkoutId): CheckoutResource|JsonResponse
    {
        $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
        ]);

        /** @var Store $store */
        $store = app('current_store');
        $checkout = Checkout::query()->withoutGlobalScopes()->where('store_id', $store->id)->findOrFail($checkoutId);

        try {
            $checkout = $this->checkoutService->selectPaymentMethod(
                $checkout,
                $request->input('payment_method'),
            );
        } catch (InvalidCheckoutTransitionException) {
            return response()->json([
                'message' => 'Invalid checkout state transition.',
            ], 422);
        }

        return new CheckoutResource($checkout);
    }

    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card_holder' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:255'],
        ]);

        /** @var Store $store */
        $store = app('current_store');
        $checkout = Checkout::query()->withoutGlobalScopes()->where('store_id', $store->id)->findOrFail($checkoutId);

        if ($checkout->status !== \App\Enums\CheckoutStatus::PaymentSelected) {
            return response()->json([
                'message' => 'Checkout is not in a valid state for payment.',
                'error_code' => 'invalid_state',
            ], 409);
        }

        $paymentDetails = [];
        if ($request->input('payment_method') === 'credit_card') {
            $paymentDetails = [
                'card_number' => str_replace(' ', '', $request->input('card_number', '')),
                'card_expiry' => $request->input('card_expiry'),
                'card_cvc' => $request->input('card_cvc'),
                'card_holder' => $request->input('card_holder'),
            ];
        }

        try {
            $order = $this->orderService->createFromCheckout($checkout, $paymentDetails);
        } catch (PaymentDeclinedException $e) {
            $messages = [
                'card_declined' => 'Your card was declined.',
                'insufficient_funds' => 'Your card has insufficient funds.',
            ];

            return response()->json([
                'message' => $messages[$e->errorCode] ?? 'Payment was declined.',
                'error_code' => $e->errorCode,
            ], 422);
        }

        $response = [
            'checkout_id' => $checkout->id,
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
        ];

        if ($request->input('payment_method') === 'bank_transfer') {
            $formattedAmount = number_format($order->total_amount / 100, 2).' '.$order->currency;
            $response['bank_transfer_instructions'] = [
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'reference' => $order->order_number,
                'amount_formatted' => $formattedAmount,
            ];
        }

        return response()->json($response);
    }
}
