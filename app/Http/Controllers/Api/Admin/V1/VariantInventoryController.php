<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\InventoryPolicy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\AdjustInventoryRequest;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\InventoryService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class VariantInventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected CurrentStore $currentStore,
    ) {}

    public function update(AdjustInventoryRequest $request, int $storeId, int $productId, int $variantId): JsonResponse
    {
        $product = Product::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($productId)
            ->firstOrFail();

        Gate::authorize('update', $product);

        $variant = $product->productVariants()->whereKey($variantId)->firstOrFail();

        $item = InventoryItem::query()->firstOrCreate(
            ['variant_id' => $variant->getKey()],
            ['store_id' => $product->store_id, 'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'policy' => InventoryPolicy::Deny]
        );

        $validated = $request->validated();

        $adjusted = $this->inventory->adjust(
            $item,
            $validated['quantity_on_hand'] ?? null,
            isset($validated['policy']) ? InventoryPolicy::from($validated['policy']) : null,
        );

        return response()->json([
            'data' => [
                'variant_id' => $variant->getKey(),
                'quantity_on_hand' => $adjusted->quantity_on_hand,
                'quantity_reserved' => $adjusted->quantity_reserved,
                'available' => $adjusted->quantity_on_hand - $adjusted->quantity_reserved,
                'policy' => $adjusted->policy->value,
                'low_stock' => $this->inventory->isLowStock($adjusted),
            ],
        ]);
    }

    public function show(int $storeId, int $productId, int $variantId): JsonResponse
    {
        $product = Product::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($productId)
            ->firstOrFail();

        Gate::authorize('view', $product);

        $variant = $product->productVariants()->whereKey($variantId)->firstOrFail();

        $item = $variant->inventoryItem;

        abort_if($item === null, 404);

        return response()->json([
            'data' => [
                'variant_id' => $variant->getKey(),
                'quantity_on_hand' => $item->quantity_on_hand,
                'quantity_reserved' => $item->quantity_reserved,
                'available' => $item->quantity_on_hand - $item->quantity_reserved,
                'policy' => $item->policy->value,
                'low_stock' => $this->inventory->isLowStock($item),
            ],
        ]);
    }
}
