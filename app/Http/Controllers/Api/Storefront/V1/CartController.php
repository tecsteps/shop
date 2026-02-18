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

        $this->cartService->addLine(
            $cartModel,
            (int) $request->validated('variant_id'),
            (int) $request->validated('quantity'),
        );

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
            $this->cartService->updateLineQuantity(
                $cartModel,
                $line,
                (int) $request->validated('quantity'),
                (int) $request->validated('cart_version'),
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

        $expectedVersion = $request->input('cart_version');

        try {
            $this->cartService->removeLine(
                $cartModel,
                $line,
                $expectedVersion ? (int) $expectedVersion : null,
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
