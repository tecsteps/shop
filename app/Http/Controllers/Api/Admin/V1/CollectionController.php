<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreCollectionRequest;
use App\Http\Requests\Api\Admin\UpdateCollectionRequest;
use App\Http\Resources\Api\Admin\CollectionResource;
use App\Models\Collection;
use App\Support\HandleGenerator;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CollectionController extends Controller
{
    public function __construct(
        protected HandleGenerator $handles,
        protected CurrentStore $currentStore,
    ) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        Gate::authorize('viewAny', Collection::class);

        $validated = $request->validate([
            'status' => 'nullable|in:draft,active,archived',
            'query' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Collection::query()
            ->where('store_id', $this->currentStore->id())
            ->withCount('products')
            ->orderByDesc('updated_at')
            ->orderBy('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['query'])) {
            $query->where('title', 'like', "%{$validated['query']}%");
        }

        $paginator = $query->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => CollectionResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreCollectionRequest $request, int $storeId): JsonResponse
    {
        Gate::authorize('create', Collection::class);

        $validated = $request->validated();
        $storeId = $this->currentStore->id();

        $handle = $validated['handle'] ?? $this->handles->generate($validated['title'], 'collections', $storeId);

        if (isset($validated['handle']) && $this->handles->exists('collections', $storeId, $handle)) {
            throw ValidationException::withMessages(['handle' => ['The handle has already been taken.']]);
        }

        $collection = Collection::query()->create([
            'store_id' => $storeId,
            'title' => $validated['title'],
            'handle' => $handle,
            'description_html' => $validated['description_html'] ?? null,
            'type' => CollectionType::from($validated['type']),
            'status' => isset($validated['status']) ? CollectionStatus::from($validated['status']) : CollectionStatus::Active,
        ]);

        $this->syncProducts($collection, $validated['product_ids'] ?? []);

        return (new CollectionResource($collection->loadCount('products')))->response()->setStatusCode(201);
    }

    public function show(int $storeId, int $collectionId): JsonResponse
    {
        $collection = $this->findCollection($collectionId);

        Gate::authorize('view', $collection);

        $collection->load(['products' => fn ($query) => $query->orderByPivot('position')->orderBy('products.id')]);
        $collection->loadCount('products');

        return (new CollectionResource($collection))->response();
    }

    public function update(UpdateCollectionRequest $request, int $storeId, int $collectionId): JsonResponse
    {
        $collection = $this->findCollection($collectionId);

        Gate::authorize('update', $collection);

        $validated = $request->validated();

        foreach (['title', 'description_html'] as $field) {
            if (array_key_exists($field, $validated)) {
                $collection->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('handle', $validated) && $validated['handle'] !== null && $validated['handle'] !== $collection->handle) {
            if ($this->handles->exists('collections', $collection->store_id, $validated['handle'], $collection->getKey())) {
                throw ValidationException::withMessages(['handle' => ['The handle has already been taken.']]);
            }

            $collection->handle = $validated['handle'];
        }

        if (array_key_exists('type', $validated)) {
            $collection->type = CollectionType::from($validated['type']);
        }

        if (array_key_exists('status', $validated)) {
            $collection->status = CollectionStatus::from($validated['status']);
        }

        $collection->save();

        if (array_key_exists('product_ids', $validated)) {
            $this->syncProducts($collection, $validated['product_ids'] ?? []);
        } else {
            if (! empty($validated['add_product_ids'])) {
                $position = (int) ($collection->products()->max('collection_products.position') ?? -1);

                foreach ($validated['add_product_ids'] as $productId) {
                    $position++;
                    $collection->products()->syncWithoutDetaching([$productId => ['position' => $position]]);
                }
            }

            if (! empty($validated['remove_product_ids'])) {
                $collection->products()->detach($validated['remove_product_ids']);
            }
        }

        return (new CollectionResource($collection->loadCount('products')))->response();
    }

    public function destroy(int $storeId, int $collectionId): JsonResponse
    {
        $collection = $this->findCollection($collectionId);

        Gate::authorize('delete', $collection);

        $collection->delete();

        return response()->json(['message' => 'Collection deleted']);
    }

    /**
     * @param  array<int, int>  $productIds
     */
    protected function syncProducts(Collection $collection, array $productIds): void
    {
        $sync = [];

        foreach (array_values($productIds) as $position => $productId) {
            $sync[$productId] = ['position' => $position];
        }

        $collection->products()->sync($sync);
    }

    protected function findCollection(int $collectionId): Collection
    {
        return Collection::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($collectionId)
            ->firstOrFail();
    }
}
