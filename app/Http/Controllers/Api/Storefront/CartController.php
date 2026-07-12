<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\DomainException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function store(Request $request): JsonResponse
    {
        $store = app('current_store');
        $customer = auth('customer')->user();
        $cart = $this->carts->create($store, $customer);
        $request->session()->put('cart_id', $cart->id);

        return response()->json(['data' => $this->data($cart)], 201);
    }

    public function show(int $cartId): JsonResponse
    {
        return response()->json(['data' => $this->data($this->cart($cartId))]);
    }

    public function addLine(Request $request, int $cartId): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        try {
            $cart = $this->cart($cartId);
            $variant = ProductVariant::withoutGlobalScopes()
                ->whereKey($validated['variant_id'])
                ->where('status', 'active')
                ->whereHas('product', fn ($query) => $query->withoutGlobalScopes()
                    ->where('store_id', app('current_store')->id)
                    ->where('status', 'active'))
                ->first();
            if ($variant === null) {
                throw ValidationException::withMessages(['variant_id' => 'The selected variant is not available.']);
            }
            $this->carts->addLine($cart, $variant, $validated['quantity'], $validated['expected_version'] ?? null);

            return response()->json(['data' => $this->data($cart->refresh())], 201);
        } catch (CartVersionMismatchException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'data' => $this->data($exception->cart)], 409);
        } catch (InsufficientInventoryException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['variant_id' => $exception->getMessage()]);
        }
    }

    public function updateLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:9999'],
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'cart_version' => ['sometimes', 'integer', 'min:1'],
        ]);
        try {
            $cart = $this->cart($cartId);
            $this->carts->updateLineQuantity($cart, $lineId, $validated['quantity'], $validated['expected_version'] ?? $validated['cart_version'] ?? null);

            return response()->json(['data' => $this->data($cart->refresh())]);
        } catch (CartVersionMismatchException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'data' => $this->data($exception->cart)], 409);
        } catch (InsufficientInventoryException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }
    }

    public function removeLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'cart_version' => ['sometimes', 'integer', 'min:1'],
        ]);
        try {
            $cart = $this->cart($cartId);
            $this->carts->removeLine($cart, $lineId, $validated['expected_version'] ?? $validated['cart_version'] ?? null);

            return response()->json(['data' => $this->data($cart->refresh())]);
        } catch (CartVersionMismatchException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'data' => $this->data($exception->cart)], 409);
        }
    }

    private function cart(int $id): Cart
    {
        return Cart::withoutGlobalScopes()->where('store_id', app('current_store')->id)->with('lines.variant.product')->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function data(Cart $cart): array
    {
        $cart->loadMissing('lines.variant.product');

        return [
            'id' => $cart->id,
            'currency' => $cart->currency,
            'status' => $cart->status,
            'cart_version' => $cart->cart_version,
            'subtotal_amount' => (int) $cart->lines->sum('line_subtotal_amount'),
            'lines' => $cart->lines->map(fn ($line): array => [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'title' => $line->variant?->product?->title,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_subtotal_amount' => $line->line_subtotal_amount,
                'line_discount_amount' => $line->line_discount_amount,
                'line_total_amount' => $line->line_total_amount,
            ])->values(),
        ];
    }
}
