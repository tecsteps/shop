<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyDiscountRequest;
use App\Http\Requests\SetCheckoutAddressRequest;
use App\Http\Resources\Storefront\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Storefront checkout API (spec 02 §2.2).
 */
class CheckoutController extends Controller
{
    public function __construct(private CheckoutService $checkoutService) {}

    /**
     * POST /api/storefront/v1/checkouts — create a checkout from a cart.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'integer'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $cart = Cart::findOrFail((int) $validated['cart_id']);

        $checkout = $this->checkoutService->createFromCart(
            $cart,
            $validated['email'],
            $request->user('customer'),
        );

        return (new CheckoutResource($checkout))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/storefront/v1/checkouts/{checkoutId} — current state (410 when expired).
     */
    public function show(int $checkoutId): CheckoutResource
    {
        $checkout = Checkout::findOrFail($checkoutId);
        $this->guardNotExpired($checkout);

        return new CheckoutResource($checkout);
    }

    /**
     * PUT /api/storefront/v1/checkouts/{checkoutId}/address.
     */
    public function setAddress(SetCheckoutAddressRequest $request, int $checkoutId): CheckoutResource
    {
        $checkout = Checkout::findOrFail($checkoutId);
        $this->guardNotExpired($checkout);

        $this->checkoutService->setAddress($checkout, $request->validated());

        return new CheckoutResource($checkout->refresh());
    }

    /**
     * PUT /api/storefront/v1/checkouts/{checkoutId}/shipping-method.
     */
    public function setShippingMethod(Request $request, int $checkoutId): CheckoutResource
    {
        $validated = $request->validate([
            'shipping_method_id' => ['required', 'integer'],
        ]);

        $checkout = Checkout::findOrFail($checkoutId);
        $this->guardNotExpired($checkout);

        $this->checkoutService->setShippingMethod($checkout, (int) $validated['shipping_method_id']);

        return new CheckoutResource($checkout->refresh());
    }

    /**
     * PUT /api/storefront/v1/checkouts/{checkoutId}/payment-method.
     */
    public function selectPaymentMethod(Request $request, int $checkoutId): CheckoutResource
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
        ]);

        $checkout = Checkout::findOrFail($checkoutId);
        $this->guardNotExpired($checkout);

        $this->checkoutService->selectPaymentMethod($checkout, $validated['payment_method']);

        return new CheckoutResource($checkout->refresh());
    }

    /**
     * POST /api/storefront/v1/checkouts/{checkoutId}/apply-discount.
     */
    public function applyDiscount(ApplyDiscountRequest $request, int $checkoutId): JsonResponse|CheckoutResource
    {
        $checkout = Checkout::findOrFail($checkoutId);
        $this->guardNotExpired($checkout);

        $result = $this->checkoutService->applyDiscount($checkout, $request->validated('code'));

        if (! $result->valid) {
            $status = in_array($result->errorCode, ['discount_expired', 'discount_usage_limit_reached'], true)
                ? 400
                : 422;

            return response()->json([
                'message' => $result->errorMessage,
                'error_code' => $result->errorCode,
            ], $status);
        }

        return new CheckoutResource($checkout->refresh());
    }

    /**
     * POST /api/storefront/v1/checkouts/{checkoutId}/pay (spec 02 §2.3):
     * charge the selected payment method via the Mock PSP and create the
     * order. Idempotent — a completed checkout returns its existing order.
     */
    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'string', 'max:25'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'string', 'max:7'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'string', 'max:4'],
            'card_holder' => ['required_if:payment_method,credit_card', 'string', 'max:255'],
        ]);

        $checkout = Checkout::findOrFail($checkoutId);
        $this->guardNotExpired($checkout);

        if ($checkout->status === CheckoutStatus::Completed) {
            $order = Order::query()->where('checkout_id', $checkout->id)->firstOrFail();

            return $this->paymentResponse($checkout, $order);
        }

        abort_if($checkout->status !== CheckoutStatus::PaymentSelected, 409, 'The checkout is not in a valid state for payment.');

        if ($checkout->payment_method->value !== $validated['payment_method']) {
            throw ValidationException::withMessages([
                'payment_method' => ['The payment method does not match the method selected for this checkout.'],
            ]);
        }

        try {
            $order = $this->checkoutService->completeCheckout($checkout, $validated);
        } catch (PaymentFailedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], 422);
        } catch (InsufficientInventoryException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => 'insufficient_inventory',
            ], 422);
        }

        return $this->paymentResponse($checkout->refresh(), $order);
    }

    /**
     * DELETE /api/storefront/v1/checkouts/{checkoutId}/discount.
     */
    public function removeDiscount(int $checkoutId): CheckoutResource
    {
        $checkout = Checkout::findOrFail($checkoutId);
        $this->guardNotExpired($checkout);

        abort_if($checkout->discount_code === null, 404, 'No discount applied to this checkout.');

        $this->checkoutService->removeDiscount($checkout);

        return new CheckoutResource($checkout->refresh());
    }

    /**
     * Build the /pay success payload (spec 02 §2.3), including bank transfer
     * instructions for deferred payments.
     */
    private function paymentResponse(Checkout $checkout, Order $order): JsonResponse
    {
        $payload = [
            'checkout_id' => $checkout->id,
            'status' => CheckoutStatus::Completed->value,
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
            $payload['bank_transfer_instructions'] = [
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'reference' => $order->order_number,
                'amount_formatted' => Money::format($order->total_amount, $order->currency),
            ];
        }

        return response()->json($payload);
    }

    /**
     * Abort with 410 when the checkout has expired.
     */
    private function guardNotExpired(Checkout $checkout): void
    {
        abort_if($checkout->isExpired(), 410, 'The checkout has expired.');
    }
}
