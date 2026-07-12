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
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkouts,
        private readonly CartService $carts,
        private readonly ShippingCalculator $shipping,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'integer'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);
        $cart = $this->cart($request, (int) $validated['cart_id']);
        try {
            $checkout = $this->checkouts->create($cart, $validated['email']);
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages(['cart_id' => $exception->getMessage()]);
        }
        $request->session()->put('api_checkout_ids', collect($request->session()->get('api_checkout_ids', []))
            ->push($checkout->id)->unique()->take(-20)->values()->all());

        return response()->json($this->data($checkout), 201);
    }

    public function show(Request $request, int $checkoutId): JsonResponse
    {
        return response()->json($this->data($this->checkout($request, $checkoutId)));
    }

    public function address(Request $request, int $checkoutId): JsonResponse
    {
        $current = $this->checkout($request, $checkoutId);
        $current->loadMissing('cart.lines.variant');
        $requiresShipping = $this->shipping->requiresShipping($current->cart);
        $validated = $request->validate([
            'email' => ['sometimes', 'email:rfc', 'max:255'],
            'shipping_address' => [$requiresShipping ? 'required' : 'nullable', 'array'],
            'shipping_address.first_name' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:255'],
            'shipping_address.last_name' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:255'],
            'shipping_address.address1' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:500'],
            'shipping_address.address2' => ['nullable', 'string', 'max:500'],
            'shipping_address.city' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:255'],
            'shipping_address.province' => ['nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:10'],
            'shipping_address.country' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:255'],
            'shipping_address.country_code' => [$requiresShipping ? 'required' : 'nullable', 'string', 'size:2'],
            'shipping_address.postal_code' => [$requiresShipping ? 'required' : 'nullable', 'string', 'max:20'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'array'],
            'use_shipping_as_billing' => ['sometimes', 'boolean'],
        ]);
        try {
            $checkout = $this->checkouts->setAddress($current, $validated);
        } catch (ShippingUnavailableException $exception) {
            throw ValidationException::withMessages(['shipping_address' => $exception->getMessage()]);
        }

        return response()->json($this->data($checkout));
    }

    public function shipping(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate(['shipping_method_id' => ['nullable', 'integer']]);
        try {
            $checkout = $this->checkouts->setShippingMethod($this->checkout($request, $checkoutId), $validated['shipping_method_id'] ?? null);
        } catch (ShippingUnavailableException $exception) {
            throw ValidationException::withMessages(['shipping_method_id' => $exception->getMessage()]);
        }

        return response()->json($this->data($checkout));
    }

    public function payment(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate(['payment_method' => ['required', 'in:credit_card,paypal,bank_transfer']]);
        try {
            $checkout = $this->checkouts->selectPaymentMethod($this->checkout($request, $checkoutId), $validated['payment_method']);
        } catch (InsufficientInventoryException $exception) {
            throw ValidationException::withMessages(['payment_method' => $exception->getMessage()]);
        }

        return response()->json($this->data($checkout));
    }

    public function discount(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:100']]);
        try {
            $checkout = $this->checkouts->applyDiscount($this->checkout($request, $checkoutId), $validated['code']);
        } catch (InvalidDiscountException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->reason,
                'errors' => ['code' => [$exception->getMessage()]],
            ], 422);
        }

        return response()->json($this->data($checkout));
    }

    public function removeDiscount(Request $request, int $checkoutId): JsonResponse
    {
        $current = $this->checkout($request, $checkoutId);
        abort_if($current->discount_code === null, 404);
        $checkout = $this->checkouts->applyDiscount($current, null);

        return response()->json($this->data($checkout));
    }

    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $details = $request->validate([
            'payment_method' => ['required', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'regex:/^(?:\d[ -]?){12,19}$/'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'date_format:m/y'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'regex:/^\d{3,4}$/'],
            'card_holder' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:255'],
        ]);
        try {
            $checkout = $this->checkout($request, $checkoutId);
            $selected = $checkout->payment_method instanceof \BackedEnum
                ? $checkout->payment_method->value
                : (string) $checkout->payment_method;
            if ($selected !== $details['payment_method']) {
                throw ValidationException::withMessages(['payment_method' => 'The payment method must match the selected checkout payment method.']);
            }
            $paymentDetails = [
                'card_number' => isset($details['card_number']) ? preg_replace('/\D+/', '', $details['card_number']) : null,
                'expiry' => $details['card_expiry'] ?? null,
                'cvc' => $details['card_cvc'] ?? null,
                'cardholder_name' => $details['card_holder'] ?? null,
            ];
            $order = $this->checkouts->completeCheckout($checkout, array_filter($paymentDetails, fn ($value): bool => $value !== null));

            $method = $order->payment_method instanceof \BackedEnum ? $order->payment_method->value : (string) $order->payment_method;
            $response = [
                'checkout_id' => $checkoutId,
                'status' => 'completed',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
                    'financial_status' => $order->financial_status instanceof \BackedEnum ? $order->financial_status->value : $order->financial_status,
                    'payment_method' => $method,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                ],
            ];
            $nextCart = $this->carts->getOrCreateForSession(app('current_store'), auth('customer')->user());
            $request->session()->put('api_cart_ids', collect($request->session()->get('api_cart_ids', []))
                ->push($nextCart->id)->unique()->take(-20)->values()->all());
            $response['next_cart'] = [
                'id' => $nextCart->id,
                'status' => $nextCart->status instanceof \BackedEnum ? $nextCart->status->value : $nextCart->status,
                'item_count' => (int) $nextCart->lines()->sum('quantity'),
            ];
            if ($method === 'bank_transfer') {
                $response['bank_transfer_instructions'] = [
                    'bank_name' => 'Mock Bank AG',
                    'iban' => 'DE89 3704 0044 0532 0130 00',
                    'bic' => 'COBADEFFXXX',
                    'reference' => $order->order_number,
                    'amount_formatted' => number_format($order->total_amount / 100, 2, '.', '').' '.$order->currency,
                ];
            }

            return response()->json($response);
        } catch (PaymentFailedException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'error_code' => $exception->errorCode], 422);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['checkout' => $exception->getMessage()]);
        }
    }

    private function cart(Request $request, int $id): Cart
    {
        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->where('status', 'active')
            ->with('lines')
            ->findOrFail($id);

        $this->assertCartAccess($request, $cart);

        return $cart;
    }

    private function checkout(Request $request, int $id): Checkout
    {
        $checkout = Checkout::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->with(['cart.lines.variant', 'store'])
            ->findOrFail($id);
        $customerId = auth('customer')->id();
        $sessionIds = collect($request->session()->get('api_checkout_ids', []))->map(fn ($value): int => (int) $value);
        $ownedByCustomer = $customerId !== null && (int) $checkout->customer_id === (int) $customerId;
        $ownedBySession = $checkout->customer_id === null && $sessionIds->contains((int) $checkout->id);
        abort_unless($ownedByCustomer || $ownedBySession, 404);
        abort_if($checkout->expires_at?->isPast() && ! in_array((string) ($checkout->status instanceof \BackedEnum ? $checkout->status->value : $checkout->status), ['completed', 'expired'], true), 410);

        return $checkout;
    }

    private function assertCartAccess(Request $request, Cart $cart): void
    {
        $customerId = auth('customer')->id();
        $sessionIds = collect($request->session()->get('api_cart_ids', []))
            ->push($request->session()->get('cart_id'))
            ->filter()
            ->map(fn ($value): int => (int) $value);
        $ownedByCustomer = $customerId !== null && (int) $cart->customer_id === (int) $customerId;
        $ownedBySession = $cart->customer_id === null && $sessionIds->contains((int) $cart->id);
        abort_unless($ownedByCustomer || $ownedBySession, 404);
    }

    /** @return array<string, mixed> */
    private function data(Checkout $checkout): array
    {
        $checkout->loadMissing(['cart.lines.variant.product', 'cart.lines.variant.optionValues', 'store']);
        $requiresShipping = $this->shipping->requiresShipping($checkout->cart);
        $rates = $checkout->shipping_address_json === null
            ? collect()
            : $this->shipping->getAvailableRates($checkout->store, (array) $checkout->shipping_address_json, $checkout->cart);

        return [
            'id' => $checkout->id,
            'store_id' => $checkout->store_id,
            'cart_id' => $checkout->cart_id,
            'customer_id' => $checkout->customer_id,
            'status' => $checkout->status instanceof \BackedEnum ? $checkout->status->value : $checkout->status,
            'email' => $checkout->email,
            'shipping_address_json' => $checkout->shipping_address_json,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->shipping_method_id,
            'payment_method' => $checkout->payment_method instanceof \BackedEnum ? $checkout->payment_method->value : $checkout->payment_method,
            'discount_code' => $checkout->discount_code,
            'lines' => $checkout->cart->lines->map(fn ($line): array => [
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant?->product?->title,
                'variant_title' => $line->variant?->optionValues->pluck('value')->implode(' / ') ?: 'Default',
                'sku' => $line->variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ])->values(),
            'totals' => $checkout->totals_json,
            'tax_provider_snapshot_json' => $checkout->tax_provider_snapshot_json,
            'requires_shipping' => $requiresShipping,
            'available_shipping_methods' => $rates->map(fn ($rate): array => [
                'id' => $rate->id,
                'name' => $rate->name,
                'type' => $rate->type instanceof \BackedEnum ? $rate->type->value : $rate->type,
                'price_amount' => (int) $rate->calculated_amount,
                'currency' => $checkout->cart->currency,
                'estimated_days_min' => data_get($rate->config_json, 'estimated_days_min'),
                'estimated_days_max' => data_get($rate->config_json, 'estimated_days_max'),
            ])->values(),
            'expires_at' => $checkout->expires_at?->toIso8601String(),
            'created_at' => $checkout->created_at?->toIso8601String(),
        ];
    }
}
