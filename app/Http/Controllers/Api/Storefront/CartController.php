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

/**
 * Storefront cart API (spec 02 §2.1).
 */
class CartController extends Controller
{
    public function __construct(private CartService $carts) {}

    /**
     * POST /api/storefront/v1/carts — create a new cart.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
        ]);

        $cart = $this->carts->create(app('current_store'), $request->user('customer'));

        if (! empty($validated['currency'])) {
            $cart->update(['currency' => strtoupper($validated['currency'])]);
        }

        return (new CartResource($cart))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/storefront/v1/carts/{cartId} — retrieve a cart.
     */
    public function show(int $cartId): CartResource
    {
        return new CartResource(Cart::findOrFail($cartId));
    }

    /**
     * POST /api/storefront/v1/carts/{cartId}/lines — add a line item.
     */
    public function addLine(Request $request, int $cartId): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['sometimes', 'integer', 'min:1'],
        ]);

        $cart = Cart::findOrFail($cartId);
        $this->assertVersion($cart, $validated['cart_version'] ?? null);

        try {
            $this->carts->addLine($cart, (int) $validated['variant_id'], (int) $validated['quantity']);
        } catch (InsufficientInventoryException) {
            throw ValidationException::withMessages([
                'variant_id' => ['The selected variant is out of stock.'],
            ]);
        }

        return (new CartResource($cart->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/storefront/v1/carts/{cartId}/lines/{lineId} — update quantity.
     */
    public function updateLine(Request $request, int $cartId, int $lineId): CartResource
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::findOrFail($cartId);
        $this->assertVersion($cart, $validated['cart_version']);

        try {
            $this->carts->updateLineQuantity($cart, $lineId, (int) $validated['quantity']);
        } catch (InsufficientInventoryException) {
            throw ValidationException::withMessages([
                'quantity' => ['The selected variant is out of stock.'],
            ]);
        }

        return new CartResource($cart->refresh());
    }

    /**
     * DELETE /api/storefront/v1/carts/{cartId}/lines/{lineId} — remove a line.
     */
    public function removeLine(Request $request, int $cartId, int $lineId): CartResource
    {
        $validated = $request->validate([
            'cart_version' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::findOrFail($cartId);
        $this->assertVersion($cart, $validated['cart_version']);

        $this->carts->removeLine($cart, $lineId);

        return new CartResource($cart->refresh());
    }

    /**
     * Verify the expected version when the client sends one.
     *
     * @throws CartVersionMismatchException
     */
    private function assertVersion(Cart $cart, ?int $expectedVersion): void
    {
        if ($expectedVersion !== null) {
            $this->carts->assertVersion($cart, $expectedVersion);
        }
    }
}
