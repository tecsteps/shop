<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\CollectionResource;
use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CollectionController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'query' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $collections = Collection::withoutGlobalScopes()
            ->withCount('products')
            ->where('store_id', $store->getKey())
            ->when(data_get($validated, 'status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(data_get($validated, 'query'), function (Builder $query, string $search): void {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->latest('updated_at')
            ->latest('id')
            ->paginate((int) data_get($validated, 'per_page', 25));

        return CollectionResource::collection($collections);
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $validated = $this->validatePayload($request, $store);

        $collection = DB::transaction(function () use ($store, $validated): Collection {
            $collection = Collection::withoutGlobalScopes()->create([
                'store_id' => $store->getKey(),
                ...$this->attributesForCreate($validated),
            ]);

            if (array_key_exists('product_ids', $validated)) {
                $this->replaceProducts($collection, $validated['product_ids']);
            }

            return $collection;
        });

        return CollectionResource::make($this->loadCollection($collection))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Store $store, Collection $collection): CollectionResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessCollectionBelongsToStore($collection, $store);

        $validated = $this->validatePayload($request, $store, $collection);

        DB::transaction(function () use ($collection, $validated): void {
            $attributes = $this->attributesForUpdate($validated);

            if ($attributes !== []) {
                $collection->update($attributes);
            }

            if (array_key_exists('product_ids', $validated)) {
                $this->replaceProducts($collection, $validated['product_ids']);
            } else {
                $this->addProducts($collection, $validated['add_product_ids'] ?? []);
                $this->removeProducts($collection, $validated['remove_product_ids'] ?? []);
            }
        });

        return CollectionResource::make($this->loadCollection($collection->refresh()));
    }

    public function destroy(Request $request, Store $store, Collection $collection): JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessCollectionBelongsToStore($collection, $store);

        $collection->delete();

        return response()->json(['message' => 'Collection deleted']);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessCollectionBelongsToStore(Collection $collection, Store $store): void
    {
        abort_unless((int) $collection->store_id === $store->getKey(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, Store $store, ?Collection $collection = null): array
    {
        $creating = $collection === null;

        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'handle' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('collections', 'handle')
                    ->where('store_id', $store->getKey())
                    ->ignore($collection?->getKey()),
            ],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'type' => [$creating ? 'required' : 'sometimes', Rule::in(['manual', 'automated'])],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $store->getKey())],
            'add_product_ids' => ['sometimes', 'array'],
            'add_product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $store->getKey())],
            'remove_product_ids' => ['sometimes', 'array'],
            'remove_product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $store->getKey())],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForCreate(array $validated): array
    {
        $title = (string) $validated['title'];
        $handle = filled($validated['handle'] ?? null) ? (string) $validated['handle'] : $title;

        return [
            'title' => $title,
            'handle' => Str::slug($handle),
            'description_html' => $validated['description_html'] ?? null,
            'type' => $validated['type'],
            'status' => $validated['status'] ?? 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForUpdate(array $validated): array
    {
        $attributes = Arr::only($validated, ['title', 'description_html', 'type', 'status']);

        if (array_key_exists('handle', $validated) && filled($validated['handle'])) {
            $attributes['handle'] = Str::slug((string) $validated['handle']);
        }

        return $attributes;
    }

    /**
     * @param  array<int, int>  $productIds
     */
    private function replaceProducts(Collection $collection, array $productIds): void
    {
        $collection->products()->sync($this->positionedProductIds($productIds));
    }

    /**
     * @param  array<int, int>  $productIds
     */
    private function addProducts(Collection $collection, array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        $startPosition = (int) $collection->products()->max('collection_products.position') + 1;

        $collection->products()->syncWithoutDetaching($this->positionedProductIds($productIds, $startPosition));
    }

    /**
     * @param  array<int, int>  $productIds
     */
    private function removeProducts(Collection $collection, array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        $collection->products()->detach($productIds);
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, array{position: int}>
     */
    private function positionedProductIds(array $productIds, int $startPosition = 0): array
    {
        return collect($productIds)
            ->unique()
            ->values()
            ->mapWithKeys(fn (int $productId, int $position): array => [$productId => ['position' => $startPosition + $position]])
            ->all();
    }

    private function loadCollection(Collection $collection): Collection
    {
        return $collection->load('products')->loadCount('products');
    }
}
