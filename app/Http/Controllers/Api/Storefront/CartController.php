<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartLine;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function store(Request $request): JsonResponse
    {
        $store = app('current_store');

        $cart = $this->cartService->create($store);

        return (new CartResource($cart))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Cart $cart): CartResource
    {
        return new CartResource($cart);
    }

    public function addLine(Request $request, Cart $cart): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:9999',
        ]);

        try {
            $this->cartService->addLine($cart, $validated['variant_id'], $validated['quantity']);
        } catch (InsufficientInventoryException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['variant_id' => ['The selected variant is out of stock.']],
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $cart->refresh();

        return (new CartResource($cart))
            ->response()
            ->setStatusCode(201);
    }

    public function updateLine(Request $request, Cart $cart, CartLine $line): JsonResponse
    {
        if ($line->cart_id !== $cart->id) {
            return response()->json(['message' => 'Cart line not found.'], 404);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:9999',
            'cart_version' => 'required|integer',
        ]);

        try {
            $this->cartService->updateLineQuantity(
                $cart,
                $line->id,
                $validated['quantity'],
                $validated['cart_version'],
            );
        } catch (CartVersionMismatchException) {
            return response()->json([
                'message' => 'Cart version conflict. Please refresh and try again.',
            ], 409);
        } catch (InsufficientInventoryException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['quantity' => ['Requested quantity exceeds available inventory.']],
            ], 422);
        }

        $cart->refresh();

        return (new CartResource($cart))->response();
    }

    public function removeLine(Request $request, Cart $cart, CartLine $line): JsonResponse
    {
        if ($line->cart_id !== $cart->id) {
            return response()->json(['message' => 'Cart line not found.'], 404);
        }

        $validated = $request->validate([
            'cart_version' => 'sometimes|integer',
        ]);

        try {
            $this->cartService->removeLine(
                $cart,
                $line->id,
                $validated['cart_version'] ?? null,
            );
        } catch (CartVersionMismatchException) {
            return response()->json([
                'message' => 'Cart version conflict. Please refresh and try again.',
            ], 409);
        }

        $cart->refresh();

        return (new CartResource($cart))->response();
    }
}
