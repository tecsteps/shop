<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, int $storeId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:draft,active,archived'],
            'query' => ['nullable', 'string'],
            'collection_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string'],
        ]);

        $query = Product::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->with([
                'media' => fn ($q) => $q->orderBy('position')->limit(1),
                'variants.inventoryItem',
            ])
            ->withCount('variants');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['query'])) {
            $search = trim((string) $validated['query']);
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%")
                    ->orWhereHas('variants', fn ($variantQuery) => $variantQuery->where('sku', 'like', "%{$search}%"));
            });
        }

        if (! empty($validated['collection_id'])) {
            $collectionId = (int) $validated['collection_id'];
            $query->whereHas('collections', fn ($q) => $q->where('collections.id', $collectionId));
        }

        $this->applySort($query, $validated['sort'] ?? 'updated_at_desc');

        $perPage = (int) ($validated['per_page'] ?? 25);
        $products = $query->paginate($perPage)->appends($request->query());

        $data = $products->getCollection()->map(function (Product $product): array {
            $inventory = $product->variants->sum(function ($variant): int {
                if (! $variant->inventoryItem) {
                    return 0;
                }

                return $variant->inventoryItem->quantity_on_hand - $variant->inventoryItem->quantity_reserved;
            });

            $featured = $product->media->first();

            return [
                'id' => $product->id,
                'store_id' => $product->store_id,
                'title' => $product->title,
                'handle' => $product->handle,
                'status' => $product->status->value,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'tags' => $product->tags,
                'variants_count' => $product->variants_count,
                'total_inventory' => $inventory,
                'published_at' => $product->published_at?->toIso8601String(),
                'created_at' => $product->created_at?->toIso8601String(),
                'updated_at' => $product->updated_at?->toIso8601String(),
                'featured_image' => [
                    'url' => $featured?->storage_key,
                    'alt_text' => $featured?->alt_text,
                ],
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show(int $storeId, int $productId): JsonResponse
    {
        $product = Product::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->with([
                'options.values',
                'variants.optionValues.option',
                'variants.inventoryItem',
                'media',
                'collections',
            ])
            ->find($productId);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $data = [
            'id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title,
            'handle' => $product->handle,
            'description_html' => $product->description_html,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'status' => $product->status->value,
            'tags' => $product->tags,
            'published_at' => $product->published_at?->toIso8601String(),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'options' => $product->options->map(function ($option): array {
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                    'position' => $option->position,
                    'values' => $option->values->map(fn ($value): array => [
                        'id' => $value->id,
                        'value' => $value->value,
                        'position' => $value->position,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'variants' => $product->variants->map(function ($variant): array {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'price_amount' => $variant->price_amount,
                    'compare_at_amount' => $variant->compare_at_amount,
                    'currency' => $variant->currency,
                    'weight_g' => $variant->weight_g,
                    'requires_shipping' => (bool) $variant->requires_shipping,
                    'is_default' => (bool) $variant->is_default,
                    'position' => $variant->position,
                    'status' => $variant->status->value,
                    'option_values' => $variant->optionValues->sortBy(fn ($value) => $value->option->position)
                        ->map(fn ($value): array => [
                            'option_name' => $value->option->name,
                            'value' => $value->value,
                        ])->values()->all(),
                    'inventory' => [
                        'quantity_on_hand' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                        'quantity_reserved' => $variant->inventoryItem?->quantity_reserved ?? 0,
                        'policy' => $variant->inventoryItem?->policy?->value,
                    ],
                ];
            })->values()->all(),
            'media' => $product->media->sortBy('position')->map(fn ($media): array => [
                'id' => $media->id,
                'type' => $media->type->value,
                'storage_key' => $media->storage_key,
                'url' => $media->storage_key,
                'alt_text' => $media->alt_text,
                'width' => $media->width,
                'height' => $media->height,
                'mime_type' => $media->mime_type,
                'byte_size' => $media->byte_size,
                'position' => $media->position,
                'status' => $media->status->value,
            ])->values()->all(),
            'collections' => $product->collections->map(fn ($collection): array => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])->values()->all(),
        ];

        return response()->json(['data' => $data]);
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'created_at_asc' => $query->orderBy('created_at'),
            'created_at_desc' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('updated_at'),
        };
    }
}
