<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Carts\AddCartLineRequest;
use App\Http\Requests\Storefront\Carts\RemoveCartLineRequest;
use App\Http\Requests\Storefront\Carts\StoreCartRequest;
use App\Http\Requests\Storefront\Carts\UpdateCartLineRequest;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{
    use ResolvesApiContext;

    public function store(StoreCartRequest $request): JsonResponse
    {
        $store = $this->currentStoreModel($request);
        $currency = strtoupper((string) ($request->validated('currency') ?? $store->default_currency ?? 'USD'));

        $service = $this->resolveService('App\\Services\\CartService');

        if ($service !== null && method_exists($service, 'create')) {
            try {
                $serviceCart = $service->create($store, null);

                if ($serviceCart instanceof Cart) {
                    return response()->json($this->cartPayload($this->loadCart($serviceCart)), 201);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $cart = Cart::query()->create([
            'store_id' => (int) $store->id,
            'customer_id' => null,
            'currency' => $currency,
            'cart_version' => 1,
            'status' => 'active',
        ]);

        return response()->json($this->cartPayload($this->loadCart($cart)), 201);
    }

    public function show(Request $request, int $cart): JsonResponse
    {
        $foundCart = $this->findCart($this->currentStoreId($request), $cart);

        if (! $foundCart instanceof Cart) {
            return response()->json([
                'message' => 'Cart not found.',
            ], 404);
        }

        return response()->json($this->cartPayload($foundCart));
    }

    public function addLine(AddCartLineRequest $request, int $cart): JsonResponse
    {
        $storeId = $this->currentStoreId($request);
        $foundCart = $this->findCart($storeId, $cart);

        if (! $foundCart instanceof Cart) {
            return response()->json([
                'message' => 'Cart not found.',
            ], 404);
        }

        if ($this->enumValue($foundCart->status) !== 'active') {
            return response()->json([
                'message' => 'Cart is not active.',
                'error_code' => 'cart_not_active',
            ], 409);
        }

        $validated = $request->validated();
        $expectedVersion = $this->expectedCartVersion($validated);

        if ($expectedVersion !== null && $expectedVersion !== (int) $foundCart->cart_version) {
            return $this->cartVersionConflict($foundCart);
        }

        $variantId = (int) $validated['variant_id'];
        $quantity = (int) $validated['quantity'];

        $service = $this->resolveService('App\\Services\\CartService');

        if ($service !== null && method_exists($service, 'addLine')) {
            try {
                $service->addLine($foundCart, $variantId, $quantity);

                return response()->json($this->cartPayload($this->loadCart($foundCart)), 201);
            } catch (\Throwable) {
                // Fall back to direct implementation below.
            }
        }

        $variant = $this->findSellableVariant($storeId, $variantId);

        if (! $variant instanceof ProductVariant) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'variant_id' => ['The selected variant is invalid.'],
                ],
            ], 422);
        }

        $line = $foundCart->lines()->where('variant_id', $variant->id)->first();
        $targetQuantity = $line instanceof CartLine
            ? (int) $line->quantity + $quantity
            : $quantity;

        $inventoryError = $this->validateInventory($variant, $targetQuantity);

        if ($inventoryError instanceof JsonResponse) {
            return $inventoryError;
        }

        $unitPrice = (int) $variant->price_amount;
        $lineSubtotal = $unitPrice * $targetQuantity;

        if ($line instanceof CartLine) {
            $line->quantity = $targetQuantity;
            $line->unit_price_amount = $unitPrice;
            $line->line_subtotal_amount = $lineSubtotal;
            $line->line_discount_amount = 0;
            $line->line_total_amount = $lineSubtotal;
            $line->save();
        } else {
            $foundCart->lines()->create([
                'variant_id' => (int) $variant->id,
                'quantity' => $targetQuantity,
                'unit_price_amount' => $unitPrice,
                'line_subtotal_amount' => $lineSubtotal,
                'line_discount_amount' => 0,
                'line_total_amount' => $lineSubtotal,
            ]);
        }

        $this->bumpCartVersion($foundCart);

        return response()->json($this->cartPayload($this->loadCart($foundCart)), 201);
    }

    public function updateLine(UpdateCartLineRequest $request, int $cart, int $line): JsonResponse
    {
        $storeId = $this->currentStoreId($request);
        $foundCart = $this->findCart($storeId, $cart);

        if (! $foundCart instanceof Cart) {
            return response()->json([
                'message' => 'Cart not found.',
            ], 404);
        }

        $foundLine = $foundCart->lines()->whereKey($line)->first();

        if (! $foundLine instanceof CartLine) {
            return response()->json([
                'message' => 'Cart line not found.',
            ], 404);
        }

        if ($this->enumValue($foundCart->status) !== 'active') {
            return response()->json([
                'message' => 'Cart is not active.',
                'error_code' => 'cart_not_active',
            ], 409);
        }

        $validated = $request->validated();
        $expectedVersion = $this->expectedCartVersion($validated);

        if ($expectedVersion !== null && $expectedVersion !== (int) $foundCart->cart_version) {
            return $this->cartVersionConflict($foundCart);
        }

        $quantity = (int) $validated['quantity'];

        $service = $this->resolveService('App\\Services\\CartService');

        if ($service !== null && method_exists($service, 'updateLineQuantity')) {
            try {
                $service->updateLineQuantity($foundCart, (int) $foundLine->id, $quantity);

                return response()->json($this->cartPayload($this->loadCart($foundCart)));
            } catch (\Throwable) {
                // Fall back to direct implementation below.
            }
        }

        $variant = $this->findSellableVariant($storeId, (int) $foundLine->variant_id);

        if (! $variant instanceof ProductVariant) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'variant_id' => ['The selected variant is invalid.'],
                ],
            ], 422);
        }

        $inventoryError = $this->validateInventory($variant, $quantity);

        if ($inventoryError instanceof JsonResponse) {
            return $inventoryError;
        }

        $unitPrice = (int) $variant->price_amount;
        $lineSubtotal = $unitPrice * $quantity;

        $foundLine->update([
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'line_subtotal_amount' => $lineSubtotal,
            'line_discount_amount' => 0,
            'line_total_amount' => $lineSubtotal,
        ]);

        $this->bumpCartVersion($foundCart);

        return response()->json($this->cartPayload($this->loadCart($foundCart)));
    }

    public function removeLine(RemoveCartLineRequest $request, int $cart, int $line): JsonResponse
    {
        $foundCart = $this->findCart($this->currentStoreId($request), $cart);

        if (! $foundCart instanceof Cart) {
            return response()->json([
                'message' => 'Cart not found.',
            ], 404);
        }

        $foundLine = $foundCart->lines()->whereKey($line)->first();

        if (! $foundLine instanceof CartLine) {
            return response()->json([
                'message' => 'Cart line not found.',
            ], 404);
        }

        if ($this->enumValue($foundCart->status) !== 'active') {
            return response()->json([
                'message' => 'Cart is not active.',
                'error_code' => 'cart_not_active',
            ], 409);
        }

        $validated = $request->validated();
        $expectedVersion = $this->expectedCartVersion($validated);

        if ($expectedVersion !== null && $expectedVersion !== (int) $foundCart->cart_version) {
            return $this->cartVersionConflict($foundCart);
        }

        $service = $this->resolveService('App\\Services\\CartService');

        if ($service !== null && method_exists($service, 'removeLine')) {
            try {
                $service->removeLine($foundCart, (int) $foundLine->id);

                return response()->json($this->cartPayload($this->loadCart($foundCart)));
            } catch (\Throwable) {
                // Fall back to direct implementation below.
            }
        }

        $foundLine->delete();

        $this->bumpCartVersion($foundCart);

        return response()->json($this->cartPayload($this->loadCart($foundCart)));
    }

    private function findCart(int $storeId, int $cartId): ?Cart
    {
        return Cart::query()
            ->where('store_id', $storeId)
            ->whereKey($cartId)
            ->with([
                'lines.variant.optionValues' => fn ($query) => $query->orderBy('position'),
                'lines.variant.inventoryItem',
                'lines.variant.product.media' => fn ($query) => $query->orderBy('position'),
            ])
            ->first();
    }

    private function loadCart(Cart $cart): Cart
    {
        $loaded = $this->findCart((int) $cart->store_id, (int) $cart->id);

        return $loaded instanceof Cart ? $loaded : $cart;
    }

    private function findSellableVariant(int $storeId, int $variantId): ?ProductVariant
    {
        return ProductVariant::query()
            ->whereKey($variantId)
            ->where('status', 'active')
            ->whereHas('product', function ($query) use ($storeId): void {
                $query->where('store_id', $storeId)
                    ->where('status', 'active');
            })
            ->with('inventoryItem')
            ->first();
    }

    private function bumpCartVersion(Cart $cart): void
    {
        $cart->cart_version = (int) $cart->cart_version + 1;
        $cart->updated_at = now();
        $cart->save();
    }

    private function expectedCartVersion(array $validated): ?int
    {
        $value = $validated['cart_version'] ?? $validated['expected_version'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    private function cartVersionConflict(Cart $cart): JsonResponse
    {
        return response()->json([
            'message' => 'Cart version conflict.',
            'error_code' => 'cart_version_conflict',
            'cart' => $this->cartPayload($this->loadCart($cart)),
        ], 409);
    }

    private function validateInventory(ProductVariant $variant, int $requestedQuantity): ?JsonResponse
    {
        $inventory = $variant->inventoryItem;

        if ($inventory === null) {
            return null;
        }

        if ($this->enumValue($inventory->policy) !== 'deny') {
            return null;
        }

        $available = max(0, (int) $inventory->quantity_on_hand - (int) $inventory->quantity_reserved);

        if ($requestedQuantity <= $available) {
            return null;
        }

        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => [
                'quantity' => ['The selected quantity exceeds available inventory.'],
            ],
        ], 422);
    }

    /**
     * @return array<string, mixed>
     */
    private function cartPayload(Cart $cart): array
    {
        $lines = $cart->lines->map(function (CartLine $line): array {
            $variant = $line->variant;
            $product = $variant?->product;
            $media = $product?->media?->first();

            $optionValues = $variant !== null && $variant->relationLoaded('optionValues')
                ? $variant->optionValues->pluck('value')->filter()->values()->all()
                : [];

            $variantTitle = $optionValues !== []
                ? implode(' / ', $optionValues)
                : ($variant?->is_default ? 'Default' : null);

            $availableQuantity = null;

            if ($variant?->inventoryItem !== null) {
                $availableQuantity = max(0, (int) $variant->inventoryItem->quantity_on_hand - (int) $variant->inventoryItem->quantity_reserved);
            }

            return [
                'id' => (int) $line->id,
                'variant_id' => (int) $line->variant_id,
                'product_title' => $product?->title,
                'variant_title' => $variantTitle,
                'sku' => $variant?->sku,
                'quantity' => (int) $line->quantity,
                'unit_price_amount' => (int) $line->unit_price_amount,
                'line_subtotal_amount' => (int) $line->line_subtotal_amount,
                'line_discount_amount' => (int) $line->line_discount_amount,
                'line_total_amount' => (int) $line->line_total_amount,
                'image_url' => $this->imageUrl($media?->storage_key),
                'requires_shipping' => (bool) ($variant?->requires_shipping ?? false),
                'available_quantity' => $availableQuantity,
            ];
        })->values();

        $subtotal = (int) $lines->sum('line_subtotal_amount');
        $discount = (int) $lines->sum('line_discount_amount');
        $total = (int) $lines->sum('line_total_amount');

        return [
            'id' => (int) $cart->id,
            'store_id' => (int) $cart->store_id,
            'customer_id' => $cart->customer_id === null ? null : (int) $cart->customer_id,
            'currency' => (string) $cart->currency,
            'cart_version' => (int) $cart->cart_version,
            'status' => (string) $this->enumValue($cart->status),
            'lines' => $lines->all(),
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'currency' => (string) $cart->currency,
                'line_count' => (int) $lines->count(),
                'item_count' => (int) $lines->sum('quantity'),
            ],
            'created_at' => $this->iso($cart->created_at),
            'updated_at' => $this->iso($cart->updated_at),
        ];
    }

    private function imageUrl(?string $storageKey): ?string
    {
        if ($storageKey === null || $storageKey === '') {
            return null;
        }

        if (str_starts_with($storageKey, 'http://') || str_starts_with($storageKey, 'https://')) {
            return $storageKey;
        }

        try {
            return Storage::disk((string) config('filesystems.default', 'public'))->url($storageKey);
        } catch (\Throwable) {
            return $storageKey;
        }
    }
}
