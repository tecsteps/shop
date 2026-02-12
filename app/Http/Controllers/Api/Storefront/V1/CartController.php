<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Enums\CartStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddCartLineRequest;
use App\Http\Requests\Api\CreateCartRequest;
use App\Http\Requests\Api\RemoveCartLineRequest;
use App\Http\Requests\Api\UpdateCartLineRequest;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function store(CreateCartRequest $request): JsonResponse
    {
        $store = app('current_store');
        $cart = $this->cartService->create($store);

        return response()->json($this->formatCart($cart), 201);
    }

    public function show(Cart $cart): JsonResponse
    {
        $cart->load('lines.variant.inventoryItem');

        return response()->json($this->formatCart($cart));
    }

    public function addLine(AddCartLineRequest $request, Cart $cart): JsonResponse
    {
        if ($cart->status !== CartStatus::Active) {
            return response()->json(['message' => 'Cart is not active.'], 422);
        }

        try {
            $this->cartService->addLine(
                $cart,
                $request->integer('variant_id'),
                $request->integer('quantity'),
            );
        } catch (InsufficientInventoryException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['variant_id' => [$e->getMessage()]],
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['variant_id' => [$e->getMessage()]],
            ], 422);
        }

        $cart->refresh();
        $cart->load('lines.variant.inventoryItem');

        return response()->json($this->formatCart($cart), 201);
    }

    public function updateLine(UpdateCartLineRequest $request, Cart $cart, int $line): JsonResponse
    {
        if ($cart->status !== CartStatus::Active) {
            return response()->json(['message' => 'Cart is not active.'], 422);
        }

        if ($cart->cart_version !== $request->integer('cart_version')) {
            return response()->json([
                'message' => 'Cart version conflict. Please refresh the cart and try again.',
            ], 409);
        }

        $cartLine = $cart->lines()->find($line);

        if (! $cartLine) {
            return response()->json(['message' => 'Cart line not found.'], 404);
        }

        try {
            $this->cartService->updateLineQuantity($cart, $line, $request->integer('quantity'));
        } catch (InsufficientInventoryException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['quantity' => [$e->getMessage()]],
            ], 422);
        }

        $cart->refresh();
        $cart->load('lines.variant.inventoryItem');

        return response()->json($this->formatCart($cart));
    }

    public function removeLine(RemoveCartLineRequest $request, Cart $cart, int $line): JsonResponse
    {
        if ($cart->status !== CartStatus::Active) {
            return response()->json(['message' => 'Cart is not active.'], 422);
        }

        if ($cart->cart_version !== $request->integer('cart_version')) {
            return response()->json([
                'message' => 'Cart version conflict. Please refresh the cart and try again.',
            ], 409);
        }

        $cartLine = $cart->lines()->find($line);

        if (! $cartLine) {
            return response()->json(['message' => 'Cart line not found.'], 404);
        }

        $this->cartService->removeLine($cart, $line);

        $cart->refresh();
        $cart->load('lines.variant.inventoryItem');

        return response()->json($this->formatCart($cart));
    }

    /** @return array<string, mixed> */
    private function formatCart(Cart $cart): array
    {
        $cart->loadMissing('lines.variant.inventoryItem');

        $lines = $cart->lines->map(fn ($line) => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_title' => $line->product_title,
            'variant_title' => $line->variant_title,
            'sku' => $line->sku,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'line_subtotal_amount' => $line->line_subtotal_amount,
            'line_discount_amount' => $line->line_discount_amount,
            'line_total_amount' => $line->line_total_amount,
            'image_url' => $line->image_url,
            'requires_shipping' => $line->requires_shipping,
            'available_quantity' => $line->variant?->inventoryItem
                ? $line->variant->inventoryItem->quantity_on_hand - $line->variant->inventoryItem->quantity_reserved
                : null,
        ]);

        $subtotal = $lines->sum('line_subtotal_amount');
        $discount = $lines->sum('line_discount_amount');
        $total = $lines->sum('line_total_amount');

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'cart_version' => $cart->cart_version,
            'status' => $cart->status->value,
            'lines' => $lines->toArray(),
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'currency' => $cart->currency,
                'line_count' => $lines->count(),
                'item_count' => $lines->sum('quantity'),
            ],
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ];
    }
}
