<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Form extends Component
{
    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /** @var array<int> */
    public array $productIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = $collection->description_html ?? '';
            $this->status = $collection->status->value;
            $this->productIds = $collection->products()->pluck('products.id')->toArray();
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->isEditing) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->productIds)) {
            $this->productIds[] = $productId;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->productIds = array_values(array_filter($this->productIds, fn ($id) => $id !== $productId));
    }

    public function save(): void
    {
        $storeId = app('current_store')->id;

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'required', 'string', 'max:255',
                Rule::unique('collections', 'handle')
                    ->where('store_id', $storeId)
                    ->ignore($this->collection?->id),
            ],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['active', 'archived'])],
        ]);

        $data = [
            'store_id' => $storeId,
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
            'type' => 'manual',
        ];

        if ($this->collection && $this->collection->exists) {
            $this->collection->update($data);
            $collection = $this->collection;
        } else {
            $collection = Collection::withoutGlobalScopes()->create($data);
            $this->collection = $collection;
        }

        $syncData = [];
        foreach ($this->productIds as $pos => $pid) {
            $syncData[$pid] = ['position' => $pos];
        }
        $collection->products()->sync($syncData);

        $this->dispatch('toast', type: 'success', message: 'Collection saved.');
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->collection !== null && $this->collection->exists;
    }

    #[Computed]
    public function searchResults(): mixed
    {
        if ($this->productSearch === '') {
            return collect();
        }

        return Product::query()
            ->where('title', 'like', "%{$this->productSearch}%")
            ->whereNotIn('id', $this->productIds)
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function assignedProducts(): mixed
    {
        if (empty($this->productIds)) {
            return collect();
        }

        return Product::withoutGlobalScopes()->whereIn('id', $this->productIds)->get();
    }

    public function render(): mixed
    {
        $breadcrumbs = [
            ['label' => 'Collections', 'url' => route('admin.collections.index')],
            ['label' => $this->isEditing ? $this->collection->title : 'Add collection'],
        ];

        return view('livewire.admin.collections.form')
            ->layout('layouts.admin', ['breadcrumbs' => $breadcrumbs]);
    }
}
