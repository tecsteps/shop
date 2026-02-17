<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Collection')]
class Form extends Component
{
    public ?Collection $collection = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:255')]
    public string $handle = '';

    #[Validate('nullable|string|max:65535')]
    public string $descriptionHtml = '';

    #[Validate('required|string|in:active,archived')]
    public string $status = 'active';

    public string $productSearch = '';

    /** @var array<int> */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->authorize('update', $collection);
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = $collection->description_html ?? '';
            $this->status = $collection->status->value;
            $this->assignedProductIds = $collection->products()->orderByPivot('position')->pluck('products.id')->toArray();
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->collection !== null && $this->collection->exists;
    }

    #[Computed]
    public function searchResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($this->productSearch)) {
            return collect();
        }

        $store = app('current_store');

        return Product::query()
            ->where('store_id', $store->id)
            ->where('title', 'like', "%{$this->productSearch}%")
            ->whereNotIn('id', $this->assignedProductIds)
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function assignedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($this->assignedProductIds)) {
            return collect();
        }

        return Product::whereIn('id', $this->assignedProductIds)
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $this->assignedProductIds));
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->assignedProductIds)) {
            $this->assignedProductIds[] = $productId;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->assignedProductIds = array_values(array_filter(
            $this->assignedProductIds,
            fn ($id) => $id !== $productId,
        ));
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            $this->authorize('update', $this->collection);
        } else {
            $this->authorize('create', Collection::class);
        }

        $store = app('current_store');

        if (empty($this->handle)) {
            $this->handle = Str::slug($this->title);
        }

        $data = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
        ];

        if ($this->isEditing) {
            $this->collection->update($data);
            $collection = $this->collection;
        } else {
            $collection = Collection::create($data);
            $this->collection = $collection;
        }

        // Sync products with position
        $syncData = [];
        foreach ($this->assignedProductIds as $position => $productId) {
            $syncData[$productId] = ['position' => $position + 1];
        }
        $collection->products()->sync($syncData);

        $this->dispatch('toast', type: 'success', message: $this->isEditing ? 'Collection updated.' : 'Collection created.');

        if (! $this->isEditing) {
            $this->redirect(route('admin.collections.edit', $collection), navigate: true);
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.collections.form');
    }
}
