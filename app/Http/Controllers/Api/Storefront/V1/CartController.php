<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Store;
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
        /** @var Store $store */
        $store = app('current_store');

        $cart = $this->cartService->create($store);

        return (new CartResource($cart))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $cartId): CartResource
    {
        /** @var Store $store */
        $store = app('current_store');

        $cart = Cart::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('lines.variant.product', 'lines.variant.inventoryItem')
            ->findOrFail($cartId);

        return new CartResource($cart);
    }

    public function addLine(Request $request, int $cartId): JsonResponse
    {
        $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        /** @var Store $store */
        $store = app('current_store');
        $cart = Cart::query()->withoutGlobalScopes()->where('store_id', $store->id)->findOrFail($cartId);

        try {
            $this->cartService->addLine(
                $cart,
                $request->integer('variant_id'),
                $request->integer('quantity'),
            );
        } catch (InsufficientInventoryException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['variant_id' => ['The selected variant is out of stock.']],
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['variant_id' => [$e->getMessage()]],
            ], 422);
        }

        return (new CartResource($cart->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function updateLine(Request $request, int $cartId, int $lineId): CartResource|JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['required', 'integer'],
        ]);

        /** @var Store $store */
        $store = app('current_store');
        $cart = Cart::query()->withoutGlobalScopes()->where('store_id', $store->id)->findOrFail($cartId);

        try {
            $this->cartService->updateLineQuantity(
                $cart,
                $lineId,
                $request->integer('quantity'),
                $request->integer('cart_version'),
            );
        } catch (CartVersionMismatchException) {
            return response()->json([
                'message' => 'Cart version conflict. Please refresh and retry.',
                'error_code' => 'version_conflict',
            ], 409);
        } catch (InsufficientInventoryException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['quantity' => ['Requested quantity exceeds available inventory.']],
            ], 422);
        }

        return new CartResource($cart->fresh());
    }

    public function removeLine(Request $request, int $cartId, int $lineId): CartResource|JsonResponse
    {
        /** @var Store $store */
        $store = app('current_store');
        $cart = Cart::query()->withoutGlobalScopes()->where('store_id', $store->id)->findOrFail($cartId);

        $cartVersion = $request->has('cart_version') ? $request->integer('cart_version') : null;

        try {
            $this->cartService->removeLine($cart, $lineId, $cartVersion);
        } catch (CartVersionMismatchException) {
            return response()->json([
                'message' => 'Cart version conflict. Please refresh and retry.',
                'error_code' => 'version_conflict',
            ], 409);
        }

        return new CartResource($cart->fresh());
    }
}
