<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollectionController extends AdminController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', '');

        $collections = Collection::query()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('handle', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, $this->enumValues(CollectionStatus::class), true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('admin.collections.index', [
            'collections' => $collections,
            'search' => $search,
            'status' => $status,
            'statuses' => CollectionStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.collections.create', [
            'statuses' => CollectionStatus::cases(),
            'products' => Product::query()->orderBy('title')->limit(200)->get(['id', 'title', 'handle']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCollection($request);

        DB::transaction(function () use ($validated): void {
            $handle = $this->uniqueHandle(
                $validated['handle'] ?: $validated['title'],
                fn (string $candidate): bool => Collection::query()->where('handle', $candidate)->exists(),
                'collection'
            );

            $collection = Collection::query()->create([
                'title' => $validated['title'],
                'handle' => $handle,
                'description_html' => $validated['description_html'],
                'type' => $validated['type'],
                'status' => $validated['status'],
            ]);

            $this->syncProducts($collection, $validated['product_ids'] ?? []);
        });

        return redirect()->route('admin.collections.index')
            ->with('status', 'Collection created.');
    }

    public function edit(Collection $collection): View
    {
        $collection->load('products:id,title');

        return view('admin.collections.edit', [
            'collection' => $collection,
            'statuses' => CollectionStatus::cases(),
            'products' => Product::query()->orderBy('title')->limit(200)->get(['id', 'title', 'handle']),
            'selectedProductIds' => $collection->products->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $validated = $this->validateCollection($request);

        DB::transaction(function () use ($validated, $collection): void {
            $handle = $this->uniqueHandle(
                $validated['handle'] ?: $validated['title'],
                fn (string $candidate): bool => Collection::query()
                    ->where('handle', $candidate)
                    ->whereKeyNot($collection->id)
                    ->exists(),
                'collection'
            );

            $collection->update([
                'title' => $validated['title'],
                'handle' => $handle,
                'description_html' => $validated['description_html'],
                'type' => $validated['type'],
                'status' => $validated['status'],
            ]);

            $this->syncProducts($collection, $validated['product_ids'] ?? []);
        });

        return redirect()->route('admin.collections.edit', $collection)
            ->with('status', 'Collection updated.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()->route('admin.collections.index')
            ->with('status', 'Collection deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateCollection(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description_html' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['manual', 'automated'])],
            'status' => ['required', Rule::in($this->enumValues(CollectionStatus::class))],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')],
        ]);
    }

    /**
     * @param  list<int>|array<int, int|string>  $productIds
     */
    protected function syncProducts(Collection $collection, array $productIds): void
    {
        $ids = Product::query()
            ->whereIn('id', $productIds)
            ->pluck('id')
            ->values()
            ->all();

        $syncData = [];

        foreach ($ids as $position => $id) {
            $syncData[$id] = ['position' => $position];
        }

        $collection->products()->sync($syncData);
    }
}
