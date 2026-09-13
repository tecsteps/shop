<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class Form extends Component
{
    use ResolvesAdminStore;

    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /** @var array<int> */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection !== null && $collection->exists) {
            abort_if($collection->store_id !== $this->store()->getKey(), 404);

            Gate::authorize('view', $collection);

            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = (string) $collection->description_html;
            $this->status = $collection->status->value;
            $this->assignedProductIds = $collection->products()
                ->orderByPivot('position')
                ->orderBy('products.id')
                ->pluck('products.id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        } else {
            Gate::authorize('create', Collection::class);

            $this->collection = null;
        }
    }

    public function updatedTitle(): void
    {
        if ($this->collection === null && $this->handle === '') {
            $this->handle = Str::slug($this->title);
        }
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->assignedProductIds, true)) {
            $this->assignedProductIds[] = $productId;
        }

        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->assignedProductIds = array_values(array_filter(
            $this->assignedProductIds,
            fn (int $id): bool => $id !== $productId
        ));
    }

    /**
     * @param  array<int>  $order
     */
    public function reorderProducts(array $order): void
    {
        $ordered = array_values(array_filter(
            array_map('intval', $order),
            fn (int $id): bool => in_array($id, $this->assignedProductIds, true)
        ));

        foreach ($this->assignedProductIds as $id) {
            if (! in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }

        $this->assignedProductIds = $ordered;
    }

    public function moveProductUp(int $productId): void
    {
        $index = array_search($productId, $this->assignedProductIds, true);

        if ($index !== false && $index > 0) {
            $ids = $this->assignedProductIds;
            [$ids[$index - 1], $ids[$index]] = [$ids[$index], $ids[$index - 1]];
            $this->assignedProductIds = $ids;
        }
    }

    public function moveProductDown(int $productId): void
    {
        $index = array_search($productId, $this->assignedProductIds, true);

        if ($index !== false && $index < count($this->assignedProductIds) - 1) {
            $ids = $this->assignedProductIds;
            [$ids[$index + 1], $ids[$index]] = [$ids[$index], $ids[$index + 1]];
            $this->assignedProductIds = $ids;
        }
    }

    public function save(): mixed
    {
        if ($this->collection !== null) {
            Gate::authorize('update', $this->collection);
        } else {
            Gate::authorize('create', Collection::class);
        }

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'handle' => 'nullable|string|max:255',
            'descriptionHtml' => 'nullable|string|max:65535',
            'status' => 'required|in:active,archived,draft',
        ]);

        $handle = $validated['handle'] !== '' && $validated['handle'] !== null
            ? $validated['handle']
            : Str::slug($validated['title']);

        $exists = Collection::query()
            ->where('store_id', $this->store()->getKey())
            ->where('handle', $handle)
            ->when($this->collection !== null, fn ($query) => $query->whereKeyNot($this->collection->getKey()))
            ->exists();

        if ($exists) {
            $this->addError('handle', 'The handle has already been taken.');

            return null;
        }

        $data = [
            'title' => $validated['title'],
            'handle' => $handle,
            'description_html' => $validated['descriptionHtml'] !== '' ? $validated['descriptionHtml'] : null,
            'status' => CollectionStatus::from($validated['status']),
        ];

        if ($this->collection !== null) {
            $this->collection->update($data);
            $this->syncProducts($this->collection);
            $this->toast('success', 'Collection saved.');

            return null;
        }

        $collection = Collection::query()->create([
            'store_id' => $this->store()->getKey(),
            'type' => CollectionType::Manual,
            ...$data,
        ]);

        $this->syncProducts($collection);
        $this->flashToast('success', 'Collection saved.');

        return redirect()->route('admin.collections.index');
    }

    public function render()
    {
        $searchResults = $this->productSearch !== ''
            ? Product::query()
                ->where('store_id', $this->store()->getKey())
                ->whereNotIn('id', $this->assignedProductIds)
                ->where('title', 'like', '%'.$this->productSearch.'%')
                ->orderBy('title')
                ->limit(10)
                ->get()
            : collect();

        $assignedProducts = $this->assignedProductIds === []
            ? collect()
            : Product::query()
                ->where('store_id', $this->store()->getKey())
                ->whereIn('id', $this->assignedProductIds)
                ->with(['productMedia' => fn ($query) => $query->orderBy('position')->orderBy('id')])
                ->get()
                ->sortBy(fn (Product $product): int => array_search($product->getKey(), $this->assignedProductIds, true) ?: 0)
                ->values();

        return view('livewire.admin.collections.form', [
            'searchResults' => $searchResults,
            'assignedProducts' => $assignedProducts,
            'isEditing' => $this->collection !== null,
        ])->layout('livewire.admin.layout.app', ['title' => $this->collection !== null ? $this->collection->title : 'Add collection']);
    }

    protected function syncProducts(Collection $collection): void
    {
        $sync = [];

        foreach (array_values($this->assignedProductIds) as $position => $productId) {
            $sync[$productId] = ['position' => $position];
        }

        $collection->products()->sync($sync);
    }
}
