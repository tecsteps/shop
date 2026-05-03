<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionConflictException;
use App\Exceptions\InvalidCartMutationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CartVersionRequest;
use App\Http\Requests\Storefront\StoreCartLineRequest;
use App\Http\Requests\Storefront\StoreCartRequest;
use App\Http\Requests\Storefront\UpdateCartLineRequest;
use App\Http\Resources\Storefront\CartResource;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function store(StoreCartRequest $request, CartService $carts): JsonResponse
    {
        $cart = $carts->create($this->currentStore());

        return (new CartResource($carts->loadForDisplay($cart)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $cartId, CartService $carts): CartResource
    {
        return new CartResource($carts->loadForDisplay($carts->findForStore($this->currentStore(), $cartId)));
    }

    public function storeLine(StoreCartLineRequest $request, int $cartId, CartService $carts): JsonResponse
    {
        $cart = $carts->findForStore($this->currentStore(), $cartId);

        try {
            $carts->addLine(
                $cart,
                (int) $request->validated('variant_id'),
                (int) $request->validated('quantity'),
                $request->integer('cart_version') ?: null,
            );
        } catch (CartVersionConflictException $exception) {
            return $this->versionConflict($exception);
        } catch (InvalidCartMutationException $exception) {
            throw ValidationException::withMessages([
                'variant_id' => [$exception->getMessage()],
            ]);
        }

        return (new CartResource($carts->loadForDisplay($cart)))
            ->response()
            ->setStatusCode(201);
    }

    public function updateLine(UpdateCartLineRequest $request, int $cartId, int $lineId, CartService $carts): CartResource|JsonResponse
    {
        $cart = $carts->findForStore($this->currentStore(), $cartId);

        try {
            $carts->updateLineQuantity(
                $cart,
                $lineId,
                (int) $request->validated('quantity'),
                (int) $request->validated('cart_version'),
            );
        } catch (CartVersionConflictException $exception) {
            return $this->versionConflict($exception);
        } catch (InvalidCartMutationException $exception) {
            throw ValidationException::withMessages([
                'quantity' => [$exception->getMessage()],
            ]);
        }

        return new CartResource($carts->loadForDisplay($cart));
    }

    public function destroyLine(CartVersionRequest $request, int $cartId, int $lineId, CartService $carts): CartResource|JsonResponse
    {
        $cart = $carts->findForStore($this->currentStore(), $cartId);

        try {
            $carts->removeLine($cart, $lineId, (int) $request->validated('cart_version'));
        } catch (CartVersionConflictException $exception) {
            return $this->versionConflict($exception);
        }

        return new CartResource($carts->loadForDisplay($cart));
    }

    private function currentStore(): Store
    {
        return app('current_store');
    }

    private function versionConflict(CartVersionConflictException $exception): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'cart' => (new CartResource($exception->cart))->resolve(),
        ], 409);
    }
}
