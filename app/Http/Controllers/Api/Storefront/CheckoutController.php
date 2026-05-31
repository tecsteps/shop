<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Storefront checkout API driving the checkout state machine via
 * {@see CheckoutService}. The checkout id acts as the access token (no auth).
 * Missing checkout -> 404; expired -> 410; invalid state -> 409/422. All amounts
 * are integers in minor units (cents).
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkouts,
        private readonly ShippingCalculator $shipping,
    ) {}

    /**
     * Create a checkout from a cart.
     */
    public function store(Request $request): JsonResponse
    {
        $store = app('current_store');

        $validated = $request->validate([
            'cart_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
        ]);

        $cart = Cart::query()->find($validated['cart_id']);

        if ($cart === null) {
            abort(404, 'The requested resource was not found.');
        }

        $checkout = $this->checkouts->startFromCart($cart);
        $checkout->update(['email' => $validated['email']]);

        return $this->respond($checkout->fresh(), 201);
    }

    /**
     * Retrieve the current checkout state and totals.
     */
    public function show(int $checkoutId): JsonResponse
    {
        $checkout = $this->resolveCheckout($checkoutId);

        return $this->respond($checkout);
    }

    /**
     * Set the shipping (and optionally billing) address.
     */
    public function setAddress(Request $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->resolveCheckout($checkoutId);

        $validated = $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.address1' => ['required', 'string', 'max:500'],
            'shipping_address.address2' => ['nullable', 'string', 'max:500'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.province' => ['nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:10'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
            'shipping_address.country_code' => ['nullable', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'array'],
            'use_shipping_as_billing' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'email' => $checkout->email,
            'shipping_address' => $this->normalizeAddress($validated['shipping_address']),
        ];

        if (empty($validated['use_shipping_as_billing'] ?? true) && ! empty($validated['billing_address'])) {
            $payload['billing_address'] = $this->normalizeAddress($validated['billing_address']);
        }

        $this->guard(fn () => $this->checkouts->setAddress($checkout, $payload));

        return $this->respond($checkout->fresh());
    }

    /**
     * Select a shipping method.
     */
    public function setShippingMethod(Request $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->resolveCheckout($checkoutId);

        $validated = $request->validate([
            'shipping_method_id' => ['required', 'integer'],
        ]);

        $this->guard(fn () => $this->checkouts->setShippingMethod($checkout, (int) $validated['shipping_method_id']));

        return $this->respond($checkout->fresh());
    }

    /**
     * Record the selected payment method (without charging).
     */
    public function setPaymentMethod(Request $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->resolveCheckout($checkoutId);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
        ]);

        $this->guard(fn () => $this->checkouts->selectPaymentMethod(
            $checkout,
            PaymentMethod::from($validated['payment_method']),
        ));

        return $this->respond($checkout->fresh());
    }

    /**
     * Apply a discount code.
     */
    public function applyDiscount(Request $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->resolveCheckout($checkoutId);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->checkouts->validateDiscountForCheckout($validated['code'], $checkout);
        } catch (InvalidDiscountException $exception) {
            return $this->discountError($exception);
        }

        $this->checkouts->applyDiscountCode($checkout, $validated['code']);

        return $this->respond($checkout->fresh());
    }

    /**
     * Remove the applied discount code.
     */
    public function removeDiscount(int $checkoutId): JsonResponse
    {
        $checkout = $this->resolveCheckout($checkoutId);

        $this->checkouts->applyDiscountCode($checkout, null);

        return $this->respond($checkout->fresh());
    }

    /**
     * Process payment and create the order.
     */
    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $checkout = $this->resolveCheckout($checkoutId);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'string'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'string'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'string'],
            'card_holder' => ['required_if:payment_method,credit_card', 'string', 'max:255'],
        ]);

        if ($checkout->status !== CheckoutStatus::PaymentSelected) {
            abort(response()->json([
                'message' => 'The checkout is not ready for payment.',
                'error_code' => 'invalid_state',
            ], 409));
        }

        try {
            $order = $this->checkouts->completeCheckout($checkout, $validated);
        } catch (PaymentFailedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], 422);
        }

        $payload = [
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

        if ($order->payment_method === PaymentMethod::BankTransfer) {
            $bank = config('shop.bank_transfer');
            $payload['bank_transfer_instructions'] = [
                'bank_name' => $bank['bank_name'],
                'iban' => $bank['iban'],
                'bic' => $bank['bic'],
                'reference' => $order->order_number,
                'amount_formatted' => number_format($order->total_amount / 100, 2).' '.$order->currency,
            ];
        }

        return response()->json($payload);
    }

    /**
     * Resolve a checkout in the current store, aborting 404 (missing) or
     * 410 (expired).
     */
    private function resolveCheckout(int $checkoutId): Checkout
    {
        $checkout = Checkout::query()->find($checkoutId);

        if ($checkout === null) {
            abort(404, 'The requested resource was not found.');
        }

        if ($checkout->status === CheckoutStatus::Expired) {
            abort(410, 'This checkout has expired.');
        }

        return $checkout->load('cart.lines.variant.product.media', 'cart.lines.variant.inventoryItem', 'cart.lines.variant.optionValues');
    }

    /**
     * Render the checkout with its currently available shipping methods.
     */
    private function respond(Checkout $checkout, int $status = 200): JsonResponse
    {
        $checkout->load('cart.lines.variant.product.media', 'cart.lines.variant.optionValues', 'cart.lines.variant.inventoryItem');

        $methods = $this->shipping->getAvailableRates(
            $checkout->store,
            $checkout->shipping_address_json ?? [],
        );

        return (new CheckoutResource($checkout))
            ->withShippingMethods($methods)
            ->response()
            ->setStatusCode($status);
    }

    /**
     * Translate invalid checkout transitions to HTTP 422 with field errors,
     * letting ValidationException from the service bubble through unchanged.
     */
    private function guard(callable $operation): void
    {
        try {
            $operation();
        } catch (InvalidCheckoutTransitionException $exception) {
            abort(response()->json([
                'message' => $exception->getMessage(),
                'error_code' => 'invalid_state',
            ], 422));
        }
    }

    /**
     * Map a discount validation failure to the documented 400/422 responses.
     */
    private function discountError(InvalidDiscountException $exception): JsonResponse
    {
        $code = match ($exception->reason) {
            'expired' => 'discount_expired',
            'usage_limit_reached' => 'discount_usage_exceeded',
            default => null,
        };

        if ($code !== null) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $code,
            ], 400);
        }

        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => ['code' => [$exception->getMessage()]],
        ], 422);
    }

    /**
     * Normalize an address payload, defaulting `country_code` from `country`.
     *
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function normalizeAddress(array $address): array
    {
        if (empty($address['country_code']) && ! empty($address['country'])) {
            $address['country_code'] = strtoupper(substr((string) $address['country'], 0, 2));
        }

        return $address;
    }
}
