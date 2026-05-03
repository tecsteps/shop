<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\SanitizeHtml;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListCollectionsRequest;
use App\Http\Requests\Admin\StoreCollectionRequest;
use App\Http\Requests\Admin\UpdateCollectionRequest;
use App\Http\Resources\Admin\CollectionResource;
use App\Models\Collection as ProductCollection;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class CollectionController extends Controller
{
    public function index(ListCollectionsRequest $request, Store $store): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = ProductCollection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('products')
            ->withCount('products')
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['query'] ?? null, fn (Builder $query, string $term) => $query->where('title', 'like', '%'.$term.'%'))
            ->latest('updated_at');

        return CollectionResource::collection(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function store(StoreCollectionRequest $request, Store $store, HandleGenerator $handles, SanitizeHtml $sanitizeHtml): JsonResponse
    {
        $validated = $request->validated();
        $collection = ProductCollection::withoutGlobalScopes()->create([
            ...Arr::only($validated, ['title', 'type', 'status']),
            'store_id' => $store->id,
            'handle' => $validated['handle'] ?? $handles->generate($validated['title'], (new ProductCollection)->getTable(), $store->id),
            'description_html' => $sanitizeHtml($validated['description_html'] ?? null),
        ]);

        $this->syncProducts($collection, $validated['product_ids'] ?? null);

        return (new CollectionResource($this->loadCollection($collection)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCollectionRequest $request, Store $store, int $collection, HandleGenerator $handles, SanitizeHtml $sanitizeHtml): CollectionResource
    {
        $existingCollection = $this->findCollection($store, $collection);
        $validated = $request->validated();
        $payload = Arr::only($validated, ['title', 'handle', 'type', 'status']);

        if (array_key_exists('description_html', $validated)) {
            $payload['description_html'] = $sanitizeHtml($validated['description_html']);
        }

        if (array_key_exists('handle', $validated) && ! $validated['handle']) {
            $payload['handle'] = $handles->generate($validated['title'] ?? $existingCollection->title, $existingCollection->getTable(), $store->id, $existingCollection->id);
        }

        $existingCollection->update($payload);

        if (array_key_exists('product_ids', $validated)) {
            $this->syncProducts($existingCollection, $validated['product_ids']);
        }

        if (array_key_exists('add_product_ids', $validated)) {
            $existingCollection->products()->syncWithoutDetaching($validated['add_product_ids'] ?? []);
        }

        if (array_key_exists('remove_product_ids', $validated)) {
            $existingCollection->products()->detach($validated['remove_product_ids'] ?? []);
        }

        return new CollectionResource($this->loadCollection($existingCollection));
    }

    public function destroy(Store $store, int $collection): JsonResponse
    {
        $this->findCollection($store, $collection)->delete();

        return response()->json(['message' => 'Collection deleted']);
    }

    private function findCollection(Store $store, int $collectionId): ProductCollection
    {
        return ProductCollection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($collectionId)
            ->firstOrFail();
    }

    private function loadCollection(ProductCollection $collection): ProductCollection
    {
        return $collection->refresh()
            ->load('products')
            ->loadCount('products');
    }

    /**
     * @param  array<int, int>|null  $productIds
     */
    private function syncProducts(ProductCollection $collection, ?array $productIds): void
    {
        if ($productIds === null) {
            return;
        }

        $collection->products()->sync($productIds);
    }
}
