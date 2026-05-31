<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Storefront cart API. Wraps {@see CartService}; the cart id acts as the access
 * token (no auth). Optimistic concurrency: a `cart_version` mismatch returns
 * 409; a missing cart returns 404; invalid input returns 422. All amounts are
 * integers in minor units (cents).
 */
class CartController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    /**
     * Create a new cart for the current store.
     */
    public function store(Request $request): JsonResponse
    {
        $store = app('current_store');

        $validated = $request->validate([
            'currency' => ['sometimes', 'string', 'size:3'],
        ]);

        $cart = $this->carts->create($store);

        if (isset($validated['currency'])) {
            $cart->update(['currency' => strtoupper($validated['currency'])]);
        }

        return (new CartResource($cart->load('lines.variant.product.media')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Retrieve a cart with its lines and computed totals.
     */
    public function show(int $cartId): JsonResponse
    {
        $cart = $this->resolveCart($cartId);

        return (new CartResource($cart))->response();
    }

    /**
     * Add a line item to the cart.
     */
    public function storeLine(Request $request, int $cartId): JsonResponse
    {
        $cart = $this->resolveCart($cartId);

        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['sometimes', 'integer'],
        ]);

        $this->guard(function () use ($cart, $validated): void {
            $this->carts->addLine(
                $cart,
                (int) $validated['variant_id'],
                (int) $validated['quantity'],
                $validated['cart_version'] ?? null,
            );
        });

        return (new CartResource($cart->fresh('lines.variant.product.media')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a cart line's quantity.
     */
    public function updateLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $cart = $this->resolveCart($cartId);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['required', 'integer'],
        ]);

        $this->assertLineExists($cart, $lineId);

        $this->guard(function () use ($cart, $lineId, $validated): void {
            $this->carts->updateLineQuantity(
                $cart,
                $lineId,
                (int) $validated['quantity'],
                (int) $validated['cart_version'],
            );
        });

        return (new CartResource($cart->fresh('lines.variant.product.media')))->response();
    }

    /**
     * Remove a line item from the cart.
     */
    public function destroyLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $cart = $this->resolveCart($cartId);

        $validated = $request->validate([
            'cart_version' => ['required', 'integer'],
        ]);

        $this->assertLineExists($cart, $lineId);

        $this->guard(function () use ($cart, $lineId, $validated): void {
            $this->carts->removeLine($cart, $lineId, (int) $validated['cart_version']);
        });

        return (new CartResource($cart->fresh('lines.variant.product.media')))->response();
    }

    /**
     * Resolve an active cart in the current store or abort 404.
     */
    private function resolveCart(int $cartId): Cart
    {
        $cart = Cart::query()->find($cartId);

        if ($cart === null) {
            abort(404, 'The requested resource was not found.');
        }

        return $cart->load('lines.variant.product.media', 'lines.variant.inventoryItem', 'lines.variant.optionValues');
    }

    private function assertLineExists(Cart $cart, int $lineId): void
    {
        if (! $cart->lines()->whereKey($lineId)->exists()) {
            abort(404, 'The requested resource was not found.');
        }
    }

    /**
     * Translate cart-service domain exceptions into the documented API errors.
     */
    private function guard(callable $operation): void
    {
        try {
            $operation();
        } catch (CartVersionMismatchException $exception) {
            abort(response()->json([
                'message' => 'The cart has been modified. Please refresh and try again.',
                'error_code' => 'version_conflict',
                'current_version' => $exception->currentVersion,
            ], 409));
        } catch (InsufficientInventoryException $exception) {
            throw ValidationException::withMessages([
                'variant_id' => ['The selected variant is out of stock.'],
            ]);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'variant_id' => [$exception->getMessage()],
            ]);
        }
    }
}
