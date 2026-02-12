<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\DiscountValidationException;
use App\Exceptions\PaymentFailedException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkoutService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email:rfc'],
        ]);

        $store = $this->resolveStore($request);

        try {
            $cart = Cart::query()
                ->where('store_id', $store->id)
                ->findOrFail($validated['cart_id']);

            $checkout = $this->checkoutService->createFromCart($cart, $validated['email']);

            return response()->json($this->checkoutPayload($checkout), 201);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cart not found.'], 404);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        }
    }

    public function show(Request $request, int $checkoutId): JsonResponse
    {
        try {
            $checkout = $this->checkoutService->findForStore($this->resolveStore($request), $checkoutId);

            return response()->json($this->checkoutPayload($checkout));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Checkout not found.'], 404);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        }
    }

    public function updateAddress(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email:rfc'],
            'shipping_address' => ['nullable', 'array'],
            'billing_address' => ['nullable', 'array'],
            'use_shipping_as_billing' => ['nullable', 'boolean'],
        ]);

        try {
            $checkout = $this->checkoutService->findForStore($this->resolveStore($request), $checkoutId);

            $checkout = $this->checkoutService->setAddress(
                $checkout,
                $validated['shipping_address'] ?? null,
                $validated['billing_address'] ?? null,
                (bool) ($validated['use_shipping_as_billing'] ?? true),
                $validated['email'] ?? null,
            );

            return response()->json($this->checkoutPayload($checkout));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Checkout not found.'], 404);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        }
    }

    public function updateShippingMethod(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate([
            'shipping_method_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $checkout = $this->checkoutService->findForStore($this->resolveStore($request), $checkoutId);
            $checkout = $this->checkoutService->setShippingMethod($checkout, $validated['shipping_method_id'] ?? null);

            return response()->json($this->checkoutPayload($checkout));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Checkout not found.'], 404);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updatePaymentMethod(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
        ]);

        try {
            $checkout = $this->checkoutService->findForStore($this->resolveStore($request), $checkoutId);
            $checkout = $this->checkoutService->selectPaymentMethod($checkout, $validated['payment_method']);

            return response()->json($this->checkoutPayload($checkout));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Checkout not found.'], 404);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function applyDiscount(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        try {
            $checkout = $this->checkoutService->findForStore($this->resolveStore($request), $checkoutId);
            $checkout = $this->checkoutService->applyDiscountCode($checkout, $validated['code']);

            return response()->json($this->checkoutPayload($checkout));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Checkout not found.'], 404);
        } catch (DiscountValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        }
    }

    public function removeDiscount(Request $request, int $checkoutId): JsonResponse
    {
        try {
            $checkout = $this->checkoutService->findForStore($this->resolveStore($request), $checkoutId);
            $checkout = $this->checkoutService->removeDiscountCode($checkout);

            return response()->json($this->checkoutPayload($checkout));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Checkout not found.'], 404);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        }
    }

    public function pay(Request $request, int $checkoutId): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['nullable', 'string'],
            'card_expiry' => ['nullable', 'string'],
            'card_cvc' => ['nullable', 'string'],
            'card_holder' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $checkout = $this->checkoutService->findForStore($this->resolveStore($request), $checkoutId);

            if (! empty($validated['payment_method']) && $checkout->payment_method?->value !== $validated['payment_method']) {
                $checkout = $this->checkoutService->selectPaymentMethod($checkout, $validated['payment_method']);
            }

            $order = $this->checkoutService->placeOrder($checkout, $validated);

            $payload = [
                'checkout_id' => $checkout->id,
                'status' => CheckoutStatus::Completed->value,
                'order' => $this->orderSummary($order),
            ];

            if ($order->payment_method === PaymentMethod::BankTransfer) {
                $payload['bank_transfer_instructions'] = [
                    'bank_name' => 'Mock Bank AG',
                    'iban' => 'DE89 3704 0044 0532 0130 00',
                    'bic' => 'COBADEFFXXX',
                    'reference' => $order->order_number,
                    'amount_formatted' => number_format($order->total_amount / 100, 2, '.', '').' '.$order->currency,
                ];
            }

            return response()->json($payload);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Checkout not found.'], 404);
        } catch (PaymentFailedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], 422);
        } catch (CheckoutStateException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->errorCode,
            ], $e->status);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(Checkout $checkout): array
    {
        $checkout->loadMissing([
            'cart.lines.variant.product',
            'cart.lines.variant.optionValues.option',
            'shippingMethod',
        ]);

        $lines = $checkout->cart->lines->map(function ($line): array {
            $variantLabel = $line->variant->optionValues
                ->sortBy(fn ($value) => $value->option->position)
                ->pluck('value')
                ->implode(' / ');

            return [
                'variant_id' => $line->variant_id,
                'product_title' => $line->variant->product->title,
                'variant_title' => $variantLabel,
                'sku' => $line->variant->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ];
        })->values()->all();

        return [
            'id' => $checkout->id,
            'store_id' => $checkout->store_id,
            'cart_id' => $checkout->cart_id,
            'customer_id' => $checkout->customer_id,
            'status' => $checkout->status->value,
            'payment_method' => $checkout->payment_method?->value,
            'email' => $checkout->email,
            'shipping_address_json' => $checkout->shipping_address_json,
            'billing_address_json' => $checkout->billing_address_json,
            'shipping_method_id' => $checkout->shipping_method_id,
            'discount_code' => $checkout->discount_code,
            'lines' => $lines,
            'totals' => $checkout->totals_json,
            'tax_provider_snapshot_json' => $checkout->tax_provider_snapshot_json,
            'available_shipping_methods' => $checkout->shipping_address_json
                ? $this->checkoutService->availableShippingMethods($checkout)
                : [],
            'expires_at' => $checkout->expires_at?->toIso8601String(),
            'created_at' => $checkout->created_at?->toIso8601String(),
            'updated_at' => $checkout->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'financial_status' => $order->financial_status->value,
            'payment_method' => $order->payment_method->value,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
        ];
    }

    private function resolveStore(Request $request): Store
    {
        $store = $request->attributes->get('current_store');

        if ($store instanceof Store) {
            return $store;
        }

        $store = app('current_store');

        if ($store instanceof Store) {
            return $store;
        }

        /** @var Store $fallback */
        $fallback = Store::query()->firstOrFail();

        return $fallback;
    }
}
