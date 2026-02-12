<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Exceptions\CartVersionMismatchException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Store;
use App\Services\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $store = $this->resolveStore($request);
        $customerId = $request->user('customer')?->id;

        $sessionCartId = $request->hasSession()
            ? (int) $request->session()->get($this->cartService->sessionKey($store), 0)
            : null;

        $cart = $this->cartService->getOrCreateBySession(
            $store,
            $sessionCartId,
            $customerId,
            $validated['currency'] ?? null,
        );

        if ($request->hasSession()) {
            $request->session()->put($this->cartService->sessionKey($store), $cart->id);
        }

        return response()->json($this->cartPayload($cart), 201);
    }

    public function show(Request $request, int $cartId): JsonResponse
    {
        try {
            $cart = $this->cartService->findForStore($this->resolveStore($request), $cartId);

            return response()->json($this->cartPayload($cart));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }
    }

    public function addLine(Request $request, int $cartId): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
            'cart_version' => ['nullable', 'integer', 'min:1'],
        ]);

        $expectedVersion = $validated['expected_version'] ?? $validated['cart_version'] ?? null;

        try {
            $cart = $this->cartService->findForStore($this->resolveStore($request), $cartId);
            $cart = $this->cartService->addLine(
                $cart,
                $validated['variant_id'],
                $validated['quantity'],
                $expectedVersion,
            );

            return response()->json($this->cartPayload($cart), 201);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cart not found.'], 404);
        } catch (CartVersionMismatchException $e) {
            return response()->json([
                'message' => 'Cart version conflict.',
                'expected_version' => $e->expectedVersion,
                'actual_version' => $e->actualVersion,
                'cart' => $e->cart ? $this->cartPayload($e->cart) : null,
            ], 409);
        } catch (InsufficientInventoryException|\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['quantity' => [$e->getMessage()]],
            ], 422);
        }
    }

    public function updateLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:9999'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
            'cart_version' => ['nullable', 'integer', 'min:1'],
        ]);

        $expectedVersion = $validated['expected_version'] ?? $validated['cart_version'] ?? null;

        try {
            $cart = $this->cartService->findForStore($this->resolveStore($request), $cartId);
            $cart = $this->cartService->updateLineQuantity(
                $cart,
                $lineId,
                $validated['quantity'],
                $expectedVersion,
            );

            return response()->json($this->cartPayload($cart));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cart or line not found.'], 404);
        } catch (CartVersionMismatchException $e) {
            return response()->json([
                'message' => 'Cart version conflict.',
                'expected_version' => $e->expectedVersion,
                'actual_version' => $e->actualVersion,
                'cart' => $e->cart ? $this->cartPayload($e->cart) : null,
            ], 409);
        } catch (InsufficientInventoryException|\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => ['quantity' => [$e->getMessage()]],
            ], 422);
        }
    }

    public function destroyLine(Request $request, int $cartId, int $lineId): JsonResponse
    {
        $validated = $request->validate([
            'expected_version' => ['nullable', 'integer', 'min:1'],
            'cart_version' => ['nullable', 'integer', 'min:1'],
        ]);

        $expectedVersion = $validated['expected_version'] ?? $validated['cart_version'] ?? null;

        try {
            $cart = $this->cartService->findForStore($this->resolveStore($request), $cartId);
            $cart = $this->cartService->removeLine($cart, $lineId, $expectedVersion);

            return response()->json($this->cartPayload($cart));
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Cart or line not found.'], 404);
        } catch (CartVersionMismatchException $e) {
            return response()->json([
                'message' => 'Cart version conflict.',
                'expected_version' => $e->expectedVersion,
                'actual_version' => $e->actualVersion,
                'cart' => $e->cart ? $this->cartPayload($e->cart) : null,
            ], 409);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function cartPayload(Cart $cart): array
    {
        $cart->loadMissing([
            'lines.variant.product.media',
            'lines.variant.inventoryItem',
            'lines.variant.optionValues.option',
        ]);

        $lines = $cart->lines->map(function ($line): array {
            $variant = $line->variant;
            $product = $variant->product;

            $variantTitle = $variant->optionValues
                ->sortBy(fn ($value) => $value->option->position)
                ->pluck('value')
                ->implode(' / ');

            $image = $product->media->sortBy('position')->first();
            $available = $variant->inventoryItem
                ? ($variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved)
                : 0;

            return [
                'id' => $line->id,
                'variant_id' => $line->variant_id,
                'product_title' => $product->title,
                'variant_title' => $variantTitle,
                'sku' => $variant->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_subtotal_amount' => $line->line_subtotal_amount,
                'line_discount_amount' => $line->line_discount_amount,
                'line_total_amount' => $line->line_total_amount,
                'image_url' => $image?->storage_key,
                'requires_shipping' => (bool) $variant->requires_shipping,
                'available_quantity' => $available,
            ];
        })->values()->all();

        return [
            'id' => $cart->id,
            'store_id' => $cart->store_id,
            'customer_id' => $cart->customer_id,
            'currency' => $cart->currency,
            'cart_version' => $cart->cart_version,
            'status' => $cart->status->value,
            'lines' => $lines,
            'totals' => $this->cartService->totals($cart),
            'created_at' => $cart->created_at?->toIso8601String(),
            'updated_at' => $cart->updated_at?->toIso8601String(),
        ];
    }

    private function resolveStore(Request $request): Store
    {
        $store = $request->attributes->get('current_store');

        if ($store instanceof Store) {
            return $store;
        }

        $store = app('current_store');

        if ($store instanceof Store) {
            return $store;
        }

        /** @var Store $fallback */
        $fallback = Store::query()->firstOrFail();

        return $fallback;
    }
}
