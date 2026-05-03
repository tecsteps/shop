<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCartOperationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\V1\DestroyCartLineRequest;
use App\Http\Requests\Api\Storefront\V1\StoreCartLineRequest;
use App\Http\Requests\Api\Storefront\V1\UpdateCartLineRequest;
use App\Http\Resources\Storefront\V1\CartResource;
use App\Models\Cart;
use App\Models\CartLine;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CartLineController extends Controller
{
    public function store(StoreCartLineRequest $request, Cart $cart, CartService $carts): CartResource|JsonResponse
    {
        try {
            $carts->addLine(
                $cart,
                (int) $request->validated('variant_id'),
                (int) $request->validated('quantity'),
                $request->expectedVersion(),
            );

            return CartResource::make($this->loadCart($cart->refresh()))
                ->response()
                ->setStatusCode(201);
        } catch (CartVersionMismatchException $exception) {
            return $this->versionMismatch($exception, $cart);
        } catch (InsufficientInventoryException|InvalidCartOperationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function update(UpdateCartLineRequest $request, Cart $cart, CartLine $cartLine, CartService $carts): CartResource|JsonResponse
    {
        abort_unless((int) $cartLine->cart_id === (int) $cart->getKey(), 404);

        try {
            $carts->updateLineQuantity(
                $cart,
                $cartLine->getKey(),
                (int) $request->validated('quantity'),
                $request->expectedVersion(),
            );

            return CartResource::make($this->loadCart($cart->refresh()));
        } catch (CartVersionMismatchException $exception) {
            return $this->versionMismatch($exception, $cart);
        } catch (InsufficientInventoryException|InvalidCartOperationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function destroy(DestroyCartLineRequest $request, Cart $cart, CartLine $cartLine, CartService $carts): CartResource|JsonResponse
    {
        abort_unless((int) $cartLine->cart_id === (int) $cart->getKey(), 404);

        try {
            $carts->removeLine($cart, $cartLine->getKey(), $request->expectedVersion());
        } catch (CartVersionMismatchException $exception) {
            return $this->versionMismatch($exception, $cart);
        } catch (InvalidCartOperationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return CartResource::make($this->loadCart($cart->refresh()));
    }

    private function versionMismatch(CartVersionMismatchException $exception, Cart $cart): JsonResponse
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'expected_cart_version' => $exception->expectedVersion,
            'current_cart_version' => $exception->currentVersion,
            'cart' => CartResource::make($this->loadCart($cart->refresh())),
        ], 409);
    }

    private function loadCart(Cart $cart): Cart
    {
        return $cart->load([
            'lines.variant.product',
            'lines.variant.optionValues.option',
        ]);
    }
}
