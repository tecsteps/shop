<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Collections\ListCollectionsRequest;
use App\Http\Requests\Admin\Collections\StoreCollectionRequest;
use App\Http\Requests\Admin\Collections\UpdateCollectionRequest;
use App\Http\Resources\Admin\CollectionResource;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    use ResolvesApiContext;

    public function index(ListCollectionsRequest $request, int $store): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $validated = $request->validated();

        $query = Collection::query()
            ->where('store_id', $storeId)
            ->withCount('products');

        if (isset($validated['status']) && is_string($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['query']) && is_string($validated['query']) && trim($validated['query']) !== '') {
            $term = trim($validated['query']);
            $like = '%'.$term.'%';

            $query->where(function ($builder) use ($like): void {
                $builder->where('title', 'like', $like)
                    ->orWhere('handle', 'like', $like);
            });
        }

        $query->orderByDesc('updated_at');

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => CollectionResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'last_page' => (int) $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreCollectionRequest $request, int $store): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $validated = $request->validated();

        $service = $this->resolveService('App\\Services\\CollectionService');

        if ($service !== null && method_exists($service, 'create')) {
            try {
                $serviceCollection = $service->create($storeId, $validated);

                if ($serviceCollection instanceof Collection) {
                    $serviceCollection->loadCount('products');

                    return response()->json([
                        'data' => CollectionResource::make($serviceCollection)->resolve(),
                    ], 201);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $collection = Collection::query()->create([
            'store_id' => $storeId,
            'title' => (string) $validated['title'],
            'handle' => $this->resolveUniqueHandle($storeId, (string) ($validated['handle'] ?? $validated['title'])),
            'description_html' => $validated['description_html'] ?? null,
            'type' => (string) ($validated['type'] ?? 'manual'),
            'status' => (string) ($validated['status'] ?? 'active'),
        ]);

        $productIds = $this->resolveStoreProductIds($storeId, $validated['product_ids'] ?? null);

        if ($productIds !== []) {
            $collection->products()->sync($productIds);
        }

        $collection->loadCount('products');

        return response()->json([
            'data' => CollectionResource::make($collection)->resolve(),
        ], 201);
    }

    public function show(Request $request, int $store, int $collection): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundCollection = $this->findStoreCollection($storeId, $collection);

        if (! $foundCollection instanceof Collection) {
            return response()->json([
                'message' => 'Collection not found.',
            ], 404);
        }

        $foundCollection->loadCount('products');

        return response()->json([
            'data' => CollectionResource::make($foundCollection)->resolve(),
        ]);
    }

    public function update(UpdateCollectionRequest $request, int $store, int $collection): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundCollection = $this->findStoreCollection($storeId, $collection);

        if (! $foundCollection instanceof Collection) {
            return response()->json([
                'message' => 'Collection not found.',
            ], 404);
        }

        $validated = $request->validated();
        $service = $this->resolveService('App\\Services\\CollectionService');

        if ($service !== null && method_exists($service, 'update')) {
            try {
                $serviceCollection = $service->update($foundCollection, $validated);

                if ($serviceCollection instanceof Collection) {
                    $serviceCollection->loadCount('products');

                    return response()->json([
                        'data' => CollectionResource::make($serviceCollection)->resolve(),
                    ]);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $payload = [];

        foreach (['title', 'description_html', 'type', 'status'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if (array_key_exists('handle', $validated) && is_string($validated['handle']) && $validated['handle'] !== '') {
            $payload['handle'] = $this->resolveUniqueHandle($storeId, $validated['handle'], (int) $foundCollection->id);
        }

        if ($payload !== []) {
            $foundCollection->fill($payload);
            $foundCollection->save();
        }

        if (array_key_exists('product_ids', $validated)) {
            $foundCollection->products()->sync($this->resolveStoreProductIds($storeId, $validated['product_ids']));
        }

        if (array_key_exists('add_product_ids', $validated)) {
            $ids = $this->resolveStoreProductIds($storeId, $validated['add_product_ids']);

            if ($ids !== []) {
                $foundCollection->products()->syncWithoutDetaching($ids);
            }
        }

        if (array_key_exists('remove_product_ids', $validated)) {
            $ids = $this->resolveStoreProductIds($storeId, $validated['remove_product_ids']);

            if ($ids !== []) {
                $foundCollection->products()->detach($ids);
            }
        }

        $foundCollection->loadCount('products');

        return response()->json([
            'data' => CollectionResource::make($foundCollection)->resolve(),
        ]);
    }

    public function destroy(Request $request, int $store, int $collection): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundCollection = $this->findStoreCollection($storeId, $collection);

        if (! $foundCollection instanceof Collection) {
            return response()->json([
                'message' => 'Collection not found.',
            ], 404);
        }

        $service = $this->resolveService('App\\Services\\CollectionService');

        if ($service !== null && method_exists($service, 'delete')) {
            try {
                $service->delete($foundCollection);
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $foundCollection->delete();

        return response()->json([
            'message' => 'Collection deleted',
        ]);
    }

    private function findStoreCollection(int $storeId, int $collectionId): ?Collection
    {
        return Collection::query()
            ->where('store_id', $storeId)
            ->whereKey($collectionId)
            ->first();
    }

    private function resolveUniqueHandle(int $storeId, string $source, ?int $ignoreCollectionId = null): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : 'collection';

        $candidate = $base;
        $suffix = 1;

        while (true) {
            $query = Collection::query()
                ->where('store_id', $storeId)
                ->where('handle', $candidate);

            if ($ignoreCollectionId !== null) {
                $query->where('id', '!=', $ignoreCollectionId);
            }

            if (! $query->exists()) {
                return $candidate;
            }

            $candidate = $base.'-'.$suffix;
            $suffix++;
        }
    }

    /**
     * @return array<int, int>
     */
    private function resolveStoreProductIds(int $storeId, mixed $candidateIds): array
    {
        if (! is_array($candidateIds) || $candidateIds === []) {
            return [];
        }

        $ids = array_values(array_filter(array_map(
            static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0,
            $candidateIds,
        ), static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        return Product::query()
            ->where('store_id', $storeId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn (int $id): int => (int) $id)
            ->values()
            ->all();
    }
}
