<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\DomainException;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\ShippingUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkouts,
        private readonly ShippingCalculator $shipping,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['cart_id' => ['required', 'integer']]);
        $cart = Cart::withoutGlobalScopes()->where('store_id', app('current_store')->id)->with('lines')->findOrFail($validated['cart_id']);
        try {
            $checkout = $this->checkouts->create($cart);
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['cart_id' => $exception->getMessage()]);
        }

        return response()->json(['data' => $this->data($checkout)], 201);
    }

    public function show(int $checkoutId): JsonResponse
    {
        return response()->json(['data' => $this->data($this->checkout($checkoutId))]);
    }

    public function address(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string'],
            'shipping_address.last_name' => ['required', 'string'],
            'shipping_address.address1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.country' => ['required_without:shipping_address.country_code', 'nullable', 'string', 'size:2'],
            'shipping_address.country_code' => ['required_without:shipping_address.country', 'nullable', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required_without:shipping_address.zip', 'nullable', 'string'],
            'shipping_address.zip' => ['required_without:shipping_address.postal_code', 'nullable', 'string'],
            'billing_address' => ['sometimes', 'array'],
        ]);
        $checkout = $this->checkouts->setAddress($this->checkout($checkoutId), $validated);
        $rates = $this->shipping->getAvailableRates(app('current_store'), (array) $checkout->shipping_address_json, $checkout->cart);

        return response()->json(['data' => $this->data($checkout), 'available_shipping_rates' => $rates]);
    }

    public function shipping(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate(['shipping_rate_id' => ['nullable', 'integer']]);
        try {
            $checkout = $this->checkouts->setShippingMethod($this->checkout($checkoutId), $validated['shipping_rate_id'] ?? null);
        } catch (ShippingUnavailableException $exception) {
            throw ValidationException::withMessages(['shipping_rate_id' => $exception->getMessage()]);
        }

        return response()->json(['data' => $this->data($checkout)]);
    }

    public function payment(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate(['payment_method' => ['required', 'in:credit_card,paypal,bank_transfer']]);
        try {
            $checkout = $this->checkouts->selectPaymentMethod($this->checkout($checkoutId), $validated['payment_method']);
        } catch (InsufficientInventoryException $exception) {
            throw ValidationException::withMessages(['payment_method' => $exception->getMessage()]);
        }

        return response()->json(['data' => $this->data($checkout)]);
    }

    public function discount(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:100']]);
        try {
            $checkout = $this->checkouts->applyDiscount($this->checkout($checkoutId), $validated['code']);
        } catch (InvalidDiscountException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->reason,
                'errors' => ['code' => [$exception->getMessage()]],
            ], 422);
        }

        return response()->json(['data' => $this->data($checkout)]);
    }

    public function removeDiscount(int $checkoutId): JsonResponse
    {
        $checkout = $this->checkouts->applyDiscount($this->checkout($checkoutId), null);

        return response()->json(['data' => $this->data($checkout)]);
    }

    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $details = $request->validate([
            'card_number' => ['sometimes', 'string', 'max:30'],
            'expiry' => ['sometimes', 'string', 'max:10'],
            'cvc' => ['sometimes', 'string', 'max:4'],
            'cardholder_name' => ['sometimes', 'string', 'max:255'],
        ]);
        try {
            $order = $this->checkouts->completeCheckout($this->checkout($checkoutId), $details);

            return response()->json(['data' => ['checkout' => $this->data($this->checkout($checkoutId)), 'order' => $order->load(['lines', 'payments'])]]);
        } catch (PaymentFailedException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'error_code' => $exception->errorCode], 422);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['checkout' => $exception->getMessage()]);
        }
    }

    private function checkout(int $id): Checkout
    {
        return Checkout::withoutGlobalScopes()->where('store_id', app('current_store')->id)->with(['cart.lines.variant', 'store'])->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function data(Checkout $checkout): array
    {
        return [
            'id' => $checkout->id,
            'cart_id' => $checkout->cart_id,
            'status' => $checkout->status,
            'email' => $checkout->email,
            'shipping_address' => $checkout->shipping_address_json,
            'billing_address' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->shipping_method_id,
            'payment_method' => $checkout->payment_method,
            'discount_code' => $checkout->discount_code,
            'totals' => $checkout->totals_json,
            'expires_at' => $checkout->expires_at,
        ];
    }
}
