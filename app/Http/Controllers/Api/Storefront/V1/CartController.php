<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\CartVersionMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\AddCartLineRequest;
use App\Http\Requests\Api\Storefront\UpdateCartLineRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');
        $cart = $this->cartService->create($store);

        return (new CartResource($cart))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $cart): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $cartModel = Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($cart);

        return (new CartResource($cartModel))->response();
    }

    public function addLine(AddCartLineRequest $request, int $cart): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $cartModel = Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($cart);

        /** @var int $variantId */
        $variantId = $request->validated('variant_id');
        /** @var int $quantity */
        $quantity = $request->validated('quantity');
        $this->cartService->addLine($cartModel, $variantId, $quantity);

        return (new CartResource($cartModel->refresh()))->response();
    }

    public function updateLine(UpdateCartLineRequest $request, int $cart, int $line): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $cartModel = Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($cart);

        try {
            /** @var int $updateQuantity */
            $updateQuantity = $request->validated('quantity');
            /** @var int $cartVersion */
            $cartVersion = $request->validated('cart_version');
            $this->cartService->updateLineQuantity(
                $cartModel,
                $line,
                $updateQuantity,
                $cartVersion,
            );
        } catch (CartVersionMismatchException) {
            return response()->json([
                'message' => 'Cart version mismatch.',
                'cart' => (new CartResource($cartModel->refresh()))->resolve(),
            ], 409);
        }

        return (new CartResource($cartModel->refresh()))->response();
    }

    public function removeLine(Request $request, int $cart, int $line): JsonResponse
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $cartModel = Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($cart);

        $expectedVersion = $request->filled('cart_version') ? $request->integer('cart_version') : null;

        try {
            $this->cartService->removeLine(
                $cartModel,
                $line,
                $expectedVersion,
            );
        } catch (CartVersionMismatchException) {
            return response()->json([
                'message' => 'Cart version mismatch.',
                'cart' => (new CartResource($cartModel->refresh()))->resolve(),
            ], 409);
        }

        return (new CartResource($cartModel->refresh()))->response();
    }
}
