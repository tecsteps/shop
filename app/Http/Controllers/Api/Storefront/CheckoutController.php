<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\InvalidShippingRateException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Support\Storefront\PriceFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected DiscountService $discountService,
    ) {}

    /**
     * POST /api/storefront/v1/checkouts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
        ]);

        $cart = Cart::query()->active()->findOrFail($validated['cart_id']);

        $checkout = $this->checkoutService->createFromCart($cart);
        $checkout->forceFill(['email' => $validated['email']])->save();

        return (new CheckoutResource($checkout->refresh()))->response()->setStatusCode(201);
    }

    /**
     * GET /api/storefront/v1/checkouts/{checkoutId}
     */
    public function show(int $checkoutId): CheckoutResource
    {
        return new CheckoutResource($this->findCheckout($checkoutId));
    }

    /**
     * PUT /api/storefront/v1/checkouts/{checkoutId}/address
     */
    public function updateAddress(Request $request, int $checkoutId): CheckoutResource
    {
        $checkout = $this->findCheckout($checkoutId);

        $payload = [
            'email' => $request->input('email', $checkout->email),
            'shipping_address' => $request->input('shipping_address'),
            'billing_address' => $request->boolean('use_shipping_as_billing', true)
                ? null
                : $request->input('billing_address'),
        ];

        try {
            $checkout = $this->checkoutService->setAddress($checkout, $payload);
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['checkout' => $exception->getMessage()]);
        }

        return new CheckoutResource($checkout);
    }

    /**
     * PUT /api/storefront/v1/checkouts/{checkoutId}/shipping-method
     */
    public function updateShippingMethod(Request $request, int $checkoutId): CheckoutResource
    {
        $checkout = $this->findCheckout($checkoutId);

        $validated = $request->validate([
            'shipping_method_id' => ['nullable', 'integer'],
        ]);

        try {
            $checkout = $this->checkoutService->setShippingMethod($checkout, $validated['shipping_method_id'] ?? null);
        } catch (InvalidShippingRateException|InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['shipping_method_id' => $exception->getMessage()]);
        }

        return new CheckoutResource($checkout);
    }

    /**
     * POST /api/storefront/v1/checkouts/{checkoutId}/apply-discount
     */
    public function applyDiscount(Request $request, int $checkoutId): CheckoutResource|JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $cart = Cart::query()->withoutGlobalScopes()->findOrFail($checkout->cart_id);

        try {
            $discount = $this->discountService->validate($validated['code'], $checkout->store, $cart);
        } catch (InvalidDiscountException $exception) {
            return $this->discountErrorResponse($exception);
        }

        $checkout->forceFill(['discount_code' => $discount->code])->save();
        $this->checkoutService->recalculate($checkout);

        return new CheckoutResource($checkout->refresh());
    }

    /**
     * DELETE /api/storefront/v1/checkouts/{checkoutId}/discount
     */
    public function removeDiscount(int $checkoutId): CheckoutResource
    {
        $checkout = $this->findCheckout($checkoutId);

        if (blank($checkout->discount_code)) {
            abort(404, 'No discount is applied to this checkout.');
        }

        $checkout->forceFill(['discount_code' => null])->save();
        $this->checkoutService->recalculate($checkout);

        return new CheckoutResource($checkout->refresh());
    }

    /**
     * PUT /api/storefront/v1/checkouts/{checkoutId}/payment-method
     */
    public function updatePaymentMethod(Request $request, int $checkoutId): CheckoutResource
    {
        $checkout = $this->findCheckout($checkoutId);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
        ]);

        try {
            $checkout = $this->checkoutService->selectPaymentMethod($checkout, $validated['payment_method']);
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['payment_method' => $exception->getMessage()]);
        }

        return new CheckoutResource($checkout);
    }

    /**
     * POST /api/storefront/v1/checkouts/{checkoutId}/pay
     */
    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->findCheckout($checkoutId);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'string', 'max:32'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'string', 'max:7'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'string', 'max:4'],
            'card_holder' => ['required_if:payment_method,credit_card', 'string', 'max:255'],
        ]);

        try {
            $order = $this->checkoutService->completeCheckout($checkout, $validated);
        } catch (InvalidCheckoutTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (PaymentFailedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], 422);
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
            ],
        ];

        if ($order->payment_method === PaymentMethod::BankTransfer) {
            $payload['bank_transfer_instructions'] = [
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'reference' => $order->order_number,
                'amount_formatted' => PriceFormatter::format($order->total_amount, $order->currency),
            ];
        }

        return response()->json($payload);
    }

    /**
     * Resolve a checkout for the current store: 404 when unknown, 410 when
     * expired (spec 02 section 2.2).
     */
    protected function findCheckout(int $checkoutId): Checkout
    {
        $checkout = Checkout::query()->findOrFail($checkoutId);

        $isExpired = $checkout->status === CheckoutStatus::Expired
            || ($checkout->status !== CheckoutStatus::Completed && $checkout->expires_at?->isPast() === true);

        if ($isExpired) {
            abort(410, 'This checkout has expired.');
        }

        return $checkout;
    }

    /**
     * Map discount validation failures to the spec 02 error envelope:
     * expired and usage-exceeded codes are business errors (400), everything
     * else is a validation error (422).
     */
    protected function discountErrorResponse(InvalidDiscountException $exception): JsonResponse
    {
        [$status, $errorCode] = match ($exception->reason) {
            'expired' => [400, 'discount_expired'],
            'usage_limit_reached' => [400, 'discount_usage_exceeded'],
            default => [422, 'discount_'.$exception->reason],
        };

        return response()->json([
            'message' => $exception->getMessage(),
            'error_code' => $errorCode,
        ], $status);
    }
}
