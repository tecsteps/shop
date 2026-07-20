<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CollectionResource;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Admin collections API (spec 02 §3.3).
 */
class CollectionController extends Controller
{
    /**
     * GET /api/admin/v1/stores/{storeId}/collections — list with
     * filtering and pagination.
     */
    public function index(Request $request, int $storeId): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(CollectionStatus::class)],
            'query' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Collection::query()->withCount('products')->orderByDesc('updated_at');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (($validated['query'] ?? '') !== '') {
            $query->where('title', 'like', '%'.$validated['query'].'%');
        }

        return CollectionResource::collection(
            $query->paginate((int) ($validated['per_page'] ?? 25)),
        );
    }

    /**
     * POST /api/admin/v1/stores/{storeId}/collections — create.
     */
    public function store(Request $request, int $storeId): JsonResponse
    {
        $validated = $request->validate($this->rules());

        /** @var Store $store */
        $store = app('current_store');

        $collection = Collection::query()->create([
            'title' => $validated['title'],
            'handle' => HandleGenerator::generate($validated['handle'] ?? $validated['title'], 'collections', $store->getKey()),
            'description_html' => $validated['description_html'] ?? null,
            'type' => CollectionType::from($validated['type']),
            'status' => isset($validated['status']) ? CollectionStatus::from($validated['status']) : CollectionStatus::Active,
        ]);

        $this->attachProducts($collection, $validated['product_ids'] ?? []);

        return (new CollectionResource($collection->load('products')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/admin/v1/stores/{storeId}/collections/{collectionId} —
     * partial update. product_ids replaces the set; add_/remove_product_ids
     * apply incremental changes.
     */
    public function update(Request $request, int $storeId, int $collectionId): CollectionResource
    {
        $validated = $request->validate(array_merge($this->rules(partial: true), [
            'add_product_ids' => ['sometimes', 'array'],
            'add_product_ids.*' => ['integer'],
            'remove_product_ids' => ['sometimes', 'array'],
            'remove_product_ids.*' => ['integer'],
        ]));

        $collection = Collection::query()->findOrFail($collectionId);

        /** @var Store $store */
        $store = app('current_store');

        $attributes = Arr::only($validated, ['title', 'description_html']);

        if (isset($validated['type'])) {
            $attributes['type'] = CollectionType::from($validated['type']);
        }

        if (isset($validated['status'])) {
            $attributes['status'] = CollectionStatus::from($validated['status']);
        }

        if (array_key_exists('handle', $validated)) {
            $attributes['handle'] = HandleGenerator::generate(
                $validated['handle'] ?: ($validated['title'] ?? $collection->title),
                'collections',
                $store->getKey(),
                $collection->getKey(),
            );
        }

        $collection->update($attributes);

        if (array_key_exists('product_ids', $validated)) {
            $this->attachProducts($collection, $validated['product_ids'] ?? [], replace: true);
        }

        if (($validated['add_product_ids'] ?? []) !== []) {
            $existing = $collection->products()->pluck('products.id');
            $this->attachProducts($collection, array_values(array_diff($validated['add_product_ids'], $existing->all())));
        }

        if (($validated['remove_product_ids'] ?? []) !== []) {
            $collection->products()->detach($validated['remove_product_ids']);
        }

        return new CollectionResource($collection->load('products')->loadCount('products'));
    }

    /**
     * DELETE /api/admin/v1/stores/{storeId}/collections/{collectionId}.
     */
    public function destroy(Request $request, int $storeId, int $collectionId): JsonResponse
    {
        Collection::query()->findOrFail($collectionId)->delete();

        return response()->json(['message' => 'Collection deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $partial = false): array
    {
        $required = fn (array $rules): array => $partial ? ['sometimes', ...$rules] : $rules;

        return [
            'title' => $required(['required', 'string', 'max:255']),
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'type' => $required(['required', Rule::enum(CollectionType::class)]),
            'status' => ['sometimes', Rule::enum(CollectionStatus::class)],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer'],
        ];
    }

    /**
     * Attach products (restricted to the current store) with sequential
     * pivot positions, optionally replacing the full set.
     *
     * @param  list<int>  $productIds
     */
    private function attachProducts(Collection $collection, array $productIds, bool $replace = false): void
    {
        $ownedIds = Product::query()->whereIn('id', $productIds)->pluck('id');

        $pivot = $ownedIds->mapWithKeys(fn (int $id, int $index): array => [$id => ['position' => $index]])->all();

        if ($replace) {
            $collection->products()->sync($pivot);
        } else {
            $collection->products()->syncWithoutDetaching($pivot);
        }
    }
}
