<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Form extends AdminComponent
{
    public ?int $collectionId = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /** @var list<int> */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection === null || ! $collection->exists) {
            Gate::authorize('create', Collection::class);

            return;
        }
        Gate::authorize('update', $collection);
        $this->collectionId = $collection->getKey();
        $this->title = $collection->title;
        $this->handle = $collection->handle;
        $this->descriptionHtml = $collection->description_html ?? '';
        $this->status = $collection->status->value;
        $this->assignedProductIds = $collection->products()->pluck('products.id')->all();
    }

    public function updatedTitle(): void
    {
        if ($this->collectionId === null) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function addProduct(int $productId): void
    {
        Product::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($productId);
        if (! in_array($productId, $this->assignedProductIds, true)) {
            $this->assignedProductIds[] = $productId;
        }
    }

    public function removeProduct(int $productId): void
    {
        $this->assignedProductIds = array_values(array_diff($this->assignedProductIds, [$productId]));
    }

    /** @param list<int> $order */
    public function reorderProducts(array $order): void
    {
        abort_unless(collect($order)->sort()->values()->all() === collect($this->assignedProductIds)->sort()->values()->all(), 422);
        $this->assignedProductIds = $order;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'], 'handle' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'], 'status' => ['required', Rule::enum(CollectionStatus::class)],
            'assignedProductIds' => ['array'], 'assignedProductIds.*' => ['integer'],
        ]);
        $collection = $this->collectionId === null
            ? new Collection(['store_id' => $this->currentStore()->getKey()])
            : Collection::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->collectionId);
        Gate::authorize($collection->exists ? 'update' : 'create', $collection->exists ? $collection : Collection::class);
        $collection->fill(['title' => $validated['title'], 'handle' => Str::slug($validated['handle']), 'description_html' => $validated['descriptionHtml'], 'status' => $validated['status']])->save();
        $validIds = Product::query()->where('store_id', $this->currentStore()->getKey())->whereKey($validated['assignedProductIds'])->pluck('id');
        $collection->products()->sync($validIds->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]]));
        $this->collectionId = $collection->getKey();
        $this->toast('Collection saved.');
    }

    #[Computed]
    public function searchResults()
    {
        if (mb_strlen($this->productSearch) < 2) {
            return collect();
        }

        return Product::query()->where('store_id', $this->currentStore()->getKey())->where('title', 'like', '%'.$this->productSearch.'%')->whereKeyNot($this->assignedProductIds)->limit(8)->get();
    }

    #[Computed]
    public function assignedProducts()
    {
        $products = Product::query()->where('store_id', $this->currentStore()->getKey())->whereKey($this->assignedProductIds)->get()->keyBy('id');

        return collect($this->assignedProductIds)->map(fn (int $id) => $products->get($id))->filter();
    }

    public function render()
    {
        return view('livewire.admin.collections.form');
    }
}
