<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function index(Request $request, int $storeId)
    {
        $this->authorize('viewAny', Collection::class);

        $collections = Collection::query()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate($request->per_page ?? 25);

        return response()->json([
            'data' => $collections->map(fn (Collection $collection) => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
                'status' => $collection->status,
                'products_count' => $collection->products()->count(),
            ]),
            'meta' => [
                'current_page' => $collections->currentPage(),
                'per_page' => $collections->perPage(),
                'total' => $collections->total(),
                'last_page' => $collections->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $storeId)
    {
        $this->authorize('create', Collection::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description_html' => ['sometimes', 'nullable', 'string'],
            'type' => ['required', 'in:manual,automated'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'product_ids' => ['sometimes', 'array'],
        ]);

        $collection = Collection::create([
            'store_id' => app('current_store')->id,
            'title' => $validated['title'],
            'handle' => $validated['handle'] ?? Str::slug($validated['title']),
            'description_html' => $validated['description_html'] ?? null,
            'type' => $validated['type'],
            'status' => $validated['status'] ?? 'active',
        ]);

        if (isset($validated['product_ids'])) {
            $collection->products()->sync($validated['product_ids']);
        }

        return response()->json(['data' => ['id' => $collection->id, 'title' => $collection->title]], 201);
    }

    public function update(Request $request, int $storeId, int $collectionId)
    {
        $collection = Collection::findOrFail($collectionId);
        $this->authorize('update', $collection);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description_html' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:draft,active,archived'],
            'product_ids' => ['sometimes', 'array'],
        ]);

        $collection->update($validated);

        if (isset($validated['product_ids'])) {
            $collection->products()->sync($validated['product_ids']);
        }

        return response()->json(['data' => ['id' => $collection->id, 'title' => $collection->title]]);
    }

    public function destroy(int $storeId, int $collectionId)
    {
        $collection = Collection::findOrFail($collectionId);
        $this->authorize('delete', $collection);

        $collection->delete();

        return response()->json(['message' => 'Collection deleted']);
    }
}
