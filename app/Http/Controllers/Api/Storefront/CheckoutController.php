<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyDiscountRequest;
use App\Http\Requests\SetCheckoutAddressRequest;
use App\Http\Resources\Storefront\CheckoutResource;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Abort with 410 when the checkout has expired.
     */
    private function guardNotExpired(Checkout $checkout): void
    {
        abort_if($checkout->isExpired(), 410, 'The checkout has expired.');
    }
}
