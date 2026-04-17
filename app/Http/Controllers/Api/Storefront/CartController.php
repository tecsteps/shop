<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartLineRequest;
use App\Http\Requests\Storefront\CreateCartRequest;
use App\Http\Requests\Storefront\DeleteCartLineRequest;
use App\Http\Requests\Storefront\UpdateCartLineRequest;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function store(CreateCartRequest $request): JsonResponse
    {
        $store = app('current_store');
        $cart = $this->cartService->create($store);

        if ($request->has('currency')) {
            $cart->update(['currency' => $request->input('currency')]);
        }

        return response()->json($this->formatCart($cart->fresh('lines')), 201);
    }

    public function show(int $cartId): JsonResponse
    {
        $cart = Cart::query()
            ->withoutGlobalScopes()
            ->where('id', $cartId)
            ->where('store_id', app('current_store')->id)
            ->with('lines.variant.product')
            ->firstOrFail();

        return response()->json($this->formatCart($cart));
    }

    public function addLine(AddCartLineRequest $request, int $cartId): JsonResponse
    {
        $cart = $this->findCart($cartId);

        try {
            $this->cartService->addLine($cart, $request->integer('variant_id'), $request->integer('quantity'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['variant_id' => [$e->getMessage()]]], 422);
        }

        return response()->json($this->formatCart($cart->fresh('lines.variant.product')), 201);
    }

    public function updateLine(UpdateCartLineRequest $request, int $cartId, int $lineId): JsonResponse
    {
        $cart = $this->findCart($cartId);

        if ($cart->cart_version !== $request->integer('cart_version')) {
            return response()->json([
                'message' => 'Cart version conflict.',
                'current_version' => $cart->cart_version,
            ], 409);
        }

        try {
            $this->cartService->updateLineQuantity($cart, $lineId, $request->integer('quantity'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['quantity' => [$e->getMessage()]]], 422);
        }

        return response()->json($this->formatCart($cart->fresh('lines.variant.product')));
    }

    public function deleteLine(DeleteCartLineRequest $request, int $cartId, int $lineId): JsonResponse
    {
        $cart = $this->findCart($cartId);

        if ($cart->cart_version !== $request->integer('cart_version')) {
            return response()->json([
                'message' => 'Cart version conflict.',
                'current_version' => $cart->cart_version,
            ], 409);
        }

        $this->cartService->removeLine($cart, $lineId);

        return response()->json($this->formatCart($cart->fresh('lines.variant.product')));
    }

    protected function findCart(int $cartId): Cart
    {
        return Cart::query()
            ->withoutGlobalScopes()
            ->where('id', $cartId)
            ->where('store_id', app('current_store')->id)
            ->with('lines.variant.product')
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatCart(Cart $cart): array
    {
        $lines = $cart->lines->map(function ($line) {
            $variant = $line->variant;
            $product = $variant?->product;

            return [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'product_title' => $product?->title,
                'variant_title' => $variant?->title ?? null,
                'sku' => $variant?->sku ?? null,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_subtotal_amount' => $line->line_subtotal_amount,
                'line_discount_amount' => $line->line_discount_amount,
                'line_total_amount' => $line->line_total_amount,
                'requires_shipping' => $variant?->requires_shipping ?? true,
            ];
        });

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'cart_version' => $cart->cart_version,
            'status' => $cart->status->value,
            'lines' => $lines,
            'totals' => [
                'subtotal' => $lines->sum('line_subtotal_amount'),
                'discount' => $lines->sum('line_discount_amount'),
                'total' => $lines->sum('line_total_amount'),
                'currency' => $cart->currency,
                'line_count' => $lines->count(),
                'item_count' => $lines->sum('quantity'),
            ],
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ];
    }
}
