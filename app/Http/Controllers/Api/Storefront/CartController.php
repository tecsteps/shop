<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\AddCartLineRequest;
use App\Http\Requests\Api\Storefront\UpdateCartLineRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function store(): JsonResponse
    {
        $store = app('current_store');
        $cart = $this->cartService->create($store);

        return (new CartResource($cart->load('lines')))->response()->setStatusCode(201);
    }

    public function show(Cart $cart): JsonResponse
    {
        $this->assertStore($cart);

        return (new CartResource($cart->load('lines')))->response();
    }

    public function addLine(AddCartLineRequest $request, Cart $cart): JsonResponse
    {
        $this->assertStore($cart);

        try {
            $this->cartService->addLine($cart, (int) $request->input('variant_id'), (int) $request->input('quantity'));
        } catch (InsufficientInventoryException $e) {
            return response()->json([
                'error' => 'insufficient_inventory',
                'variant_id' => $e->variantId,
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return (new CartResource($cart->refresh()->load('lines')))->response();
    }

    public function updateLine(UpdateCartLineRequest $request, Cart $cart, int $line): JsonResponse
    {
        $this->assertStore($cart);

        try {
            $this->cartService->updateLineQuantity($cart, $line, (int) $request->input('quantity'));
        } catch (InsufficientInventoryException $e) {
            return response()->json(['error' => 'insufficient_inventory'], 422);
        }

        return (new CartResource($cart->refresh()->load('lines')))->response();
    }

    public function removeLine(Cart $cart, int $line): JsonResponse
    {
        $this->assertStore($cart);

        $this->cartService->removeLine($cart, $line);

        return (new CartResource($cart->refresh()->load('lines')))->response();
    }

    protected function assertStore(Cart $cart): void
    {
        $store = app('current_store');

        if ((int) $cart->store_id !== (int) $store->getKey()) {
            abort(404);
        }
    }
}
