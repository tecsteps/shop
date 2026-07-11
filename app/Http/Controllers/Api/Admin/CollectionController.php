<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller
{
    public function __construct(private readonly HandleGenerator $handleGenerator) {}

    public function index(Store $store): AnonymousResourceCollection
    {
        $this->ensureStore($store);

        return CollectionResource::collection(Collection::query()->withCount('products')->latest()->paginate(15));
    }

    public function store(StoreCollectionRequest $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);
        $collection = DB::transaction(function () use ($request, $store): Collection {
            $data = $request->validated();
            $productIds = Arr::pull($data, 'product_ids', []);
            $data['store_id'] = $store->id;
            $data['handle'] = $this->handleGenerator->generate($data['handle'] ?? $data['title'], 'collections', $store->id);
            $collection = Collection::query()->create($data);
            $collection->products()->sync(collect($productIds)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]]));

            return $collection;
        });

        return (new CollectionResource($collection->load('products')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateCollectionRequest $request, Store $store, Collection $collection): CollectionResource
    {
        $this->ensureRelated($store, $collection);
        $data = $request->validated();
        $productIds = Arr::pull($data, 'product_ids');

        if (isset($data['handle'])) {
            $data['handle'] = $this->handleGenerator->generate($data['handle'], 'collections', $store->id, $collection->id);
        }

        $collection->update($data);

        if (is_array($productIds)) {
            $collection->products()->sync(collect($productIds)->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]]));
        }

        return new CollectionResource($collection->refresh()->load('products'));
    }

    public function destroy(Store $store, Collection $collection): JsonResponse
    {
        $this->ensureRelated($store, $collection);
        $collection->delete();

        return response()->json(['deleted' => true]);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }

    private function ensureRelated(Store $store, Collection $collection): void
    {
        $this->ensureStore($store);
        abort_unless($collection->store_id === $store->id, Response::HTTP_NOT_FOUND);
    }
}
