<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Exceptions\CartVersionMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\CartResource;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    /**
     * POST /api/storefront/v1/carts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $cart = $this->cartService->create(app('current_store'));

        if (isset($validated['currency'])) {
            $cart->forceFill(['currency' => strtoupper($validated['currency'])])->save();
        }

        return (new CartResource($cart))->response()->setStatusCode(201);
    }

    /**
     * GET /api/storefront/v1/carts/{cartId}
     */
    public function show(int $cartId): CartResource
    {
        return new CartResource($this->findCart($cartId));
    }

    /**
     * POST /api/storefront/v1/carts/{cartId}/lines
     */
    public function storeLine(Request $request, int $cartId): CartResource|JsonResponse
    {
        $cart = $this->findCart($cartId);

        $validated = $request->validate([
            'variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['nullable', 'integer'],
        ]);

        try {
            $this->assertVersionWhenProvided($cart, $request);
            $this->cartService->addLine($cart, $validated['variant_id'], $validated['quantity']);
        } catch (CartVersionMismatchException) {
            return $this->versionConflictResponse($cart);
        }

        return new CartResource($cart->refresh());
    }

    /**
     * PUT /api/storefront/v1/carts/{cartId}/lines/{lineId}
     */
    public function updateLine(Request $request, int $cartId, int $lineId): CartResource|JsonResponse
    {
        $cart = $this->findCart($cartId);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['required_without:expected_version', 'integer'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        try {
            $this->cartService->assertVersion($cart, $this->expectedVersion($request));
            $this->cartService->updateLineQuantity($cart, $lineId, $validated['quantity']);
        } catch (CartVersionMismatchException) {
            return $this->versionConflictResponse($cart);
        }

        return new CartResource($cart->refresh());
    }

    /**
     * DELETE /api/storefront/v1/carts/{cartId}/lines/{lineId}
     */
    public function destroyLine(Request $request, int $cartId, int $lineId): CartResource|JsonResponse
    {
        $cart = $this->findCart($cartId);

        $request->validate([
            'cart_version' => ['required_without:expected_version', 'integer'],
            'expected_version' => ['nullable', 'integer'],
        ]);

        try {
            $this->cartService->assertVersion($cart, $this->expectedVersion($request));
            $this->cartService->removeLine($cart, $lineId);
        } catch (CartVersionMismatchException) {
            return $this->versionConflictResponse($cart);
        }

        return new CartResource($cart->refresh());
    }

    /**
     * Resolve an active cart for the current store or fail with 404.
     */
    protected function findCart(int $cartId): Cart
    {
        return Cart::query()->active()->findOrFail($cartId);
    }

    /**
     * The optimistic concurrency version the client last saw. Spec 02 names
     * the field "cart_version"; the roadmap test table uses
     * "expected_version", so both are accepted.
     */
    protected function expectedVersion(Request $request): int
    {
        return (int) $request->input('cart_version', $request->input('expected_version'));
    }

    /**
     * @throws CartVersionMismatchException
     */
    protected function assertVersionWhenProvided(Cart $cart, Request $request): void
    {
        if ($request->filled('cart_version') || $request->filled('expected_version')) {
            $this->cartService->assertVersion($cart, $this->expectedVersion($request));
        }
    }

    /**
     * 409 conflict envelope with the current cart state (spec 02 section 10).
     */
    protected function versionConflictResponse(Cart $cart): JsonResponse
    {
        return response()->json([
            'message' => __('The cart has been modified. Please refresh and try again.'),
            'error_code' => 'version_conflict',
            'current_version' => $cart->cart_version,
            'cart' => (new CartResource($cart))->resolve(),
        ], 409);
    }
}
