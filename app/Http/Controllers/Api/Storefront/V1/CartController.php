<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\CartVersionMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\AddCartLineRequest;
use App\Http\Requests\Api\Storefront\CreateCartRequest;
use App\Http\Requests\Api\Storefront\RemoveCartLineRequest;
use App\Http\Requests\Api\Storefront\UpdateCartLineRequest;
use App\Http\Resources\Api\Storefront\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        protected CartService $carts,
        protected CurrentStore $currentStore,
    ) {}

    public function store(CreateCartRequest $request): JsonResponse
    {
        $store = $this->currentStore->get();

        abort_if($store === null, 404);

        $cart = $this->carts->create($store, null, $request->validated('currency'));

        return response()->json((new CartResource($this->loadCart($cart)))->resolve(), 201);
    }

    public function show(int $cartId): JsonResponse
    {
        $cart = $this->findCart($cartId);

        return response()->json((new CartResource($cart))->resolve());
    }

    public function addLine(AddCartLineRequest $request, int $cartId): JsonResponse
    {
        $cart = $this->findCart($cartId);
        $validated = $request->validated();

        try {
            $this->carts->addLine($cart, $validated['variant_id'], $validated['quantity'], $validated['cart_version'] ?? null);
        } catch (CartVersionMismatchException $exception) {
            return $this->versionConflict($cart->refresh(), $exception->currentVersion);
        }

        return response()->json((new CartResource($this->loadCart($cart->refresh())))->resolve(), 201);
    }

    public function updateLine(UpdateCartLineRequest $request, int $cartId, int $lineId): JsonResponse
    {
        $cart = $this->findCart($cartId);
        $validated = $request->validated();

        if ($cart->cartLines()->whereKey($lineId)->doesntExist()) {
            abort(404);
        }

        try {
            $this->carts->updateLineQuantity($cart, $lineId, $validated['quantity'], $validated['cart_version']);
        } catch (CartVersionMismatchException $exception) {
            return $this->versionConflict($cart->refresh(), $exception->currentVersion);
        }

        return response()->json((new CartResource($this->loadCart($cart->refresh())))->resolve());
    }

    public function removeLine(RemoveCartLineRequest $request, int $cartId, int $lineId): JsonResponse
    {
        $cart = $this->findCart($cartId);

        if ($cart->cartLines()->whereKey($lineId)->doesntExist()) {
            abort(404);
        }

        try {
            $this->carts->removeLine($cart, $lineId, $request->validated('cart_version'));
        } catch (CartVersionMismatchException $exception) {
            return $this->versionConflict($cart->refresh(), $exception->currentVersion);
        }

        return response()->json((new CartResource($this->loadCart($cart->refresh())))->resolve());
    }

    protected function findCart(int $cartId): Cart
    {
        return $this->loadCart(
            Cart::query()
                ->where('store_id', $this->currentStore->id())
                ->whereKey($cartId)
                ->firstOrFail()
        );
    }

    protected function loadCart(Cart $cart): Cart
    {
        return $cart->load([
            'cartLines.variant.product',
            'cartLines.variant.optionValues',
            'cartLines.variant.inventoryItem',
            'cartLines.variant.product.productMedia',
        ]);
    }

    protected function versionConflict(Cart $cart, int $currentVersion): JsonResponse
    {
        return response()->json([
            'message' => 'The cart has been modified. Please refresh and try again.',
            'error_code' => 'version_conflict',
            'current_version' => $currentVersion,
            'cart' => (new CartResource($this->loadCart($cart)))->resolve(),
        ], 409);
    }
}
