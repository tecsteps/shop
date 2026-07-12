<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Support\HandleGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CollectionController extends Controller
{
    public function __construct(private readonly HandleGenerator $handles) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['status' => ['sometimes', 'in:draft,active,archived'], 'query' => ['sometimes', 'string'], 'per_page' => ['sometimes', 'integer', 'max:100']]);
        $items = Collection::withoutGlobalScopes()->where('store_id', $storeId)
            ->when(isset($data['status']), fn ($q) => $q->where('status', $data['status']))
            ->when(isset($data['query']), fn ($q) => $q->where('title', 'like', '%'.$data['query'].'%'))
            ->withCount('products')->paginate($data['per_page'] ?? 25);

        return response()->json(['data' => $items->items(), 'meta' => ['total' => $items->total(), 'current_page' => $items->currentPage()]]);
    }

    public function store(Request $request, int $storeId): JsonResponse
    {
        $data = $this->validateData($request, $storeId);
        $collection = Collection::withoutGlobalScopes()->create([
            ...$data,
            'store_id' => $storeId,
            'handle' => $data['handle'] ?? $this->handles->generate($data['title'], 'collections', $storeId),
        ]);
        $collection->products()->sync($this->positions($data['product_ids'] ?? []));

        return response()->json(['data' => $collection->load('products')], 201);
    }

    public function update(Request $request, int $storeId, int $collectionId): JsonResponse
    {
        $collection = $this->find($storeId, $collectionId);
        $data = $this->validateData($request, $storeId, $collectionId, true);
        $collection->update(collect($data)->except('product_ids')->all());
        if (array_key_exists('product_ids', $data)) {
            $collection->products()->sync($this->positions($data['product_ids']));
        }

        return response()->json(['data' => $collection->refresh()->load('products')]);
    }

    public function destroy(int $storeId, int $collectionId): JsonResponse
    {
        $this->find($storeId, $collectionId)->delete();

        return response()->json(['message' => 'Collection deleted.']);
    }

    /** @return array<string, mixed> */
    private function validateData(Request $request, int $storeId, ?int $ignore = null, bool $partial = false): array
    {
        return $request->validate([
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'handle' => ['sometimes', 'string', Rule::unique('collections')->where('store_id', $storeId)->ignore($ignore)],
            'description_html' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:manual,automated'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer'],
        ]);
    }

    /** @param list<int> $ids @return array<int, array{position: int}> */
    private function positions(array $ids): array
    {
        return collect(array_values($ids))->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])->all();
    }

    private function find(int $storeId, int $id): Collection
    {
        return Collection::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($id);
    }
}
