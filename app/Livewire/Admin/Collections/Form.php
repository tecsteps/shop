<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
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
        if ($collection?->exists) {
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = $collection->description_html ?? '';
            $this->status = $collection->status->value;
            $this->assignedProductIds = $collection->products()->orderByPivot('position')->pluck('products.id')->toArray();
        }
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
            fn ($id) => $id !== $productId
        ));
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,active,archived'],
        ]);

        $store = app('current_store');

        if (! $this->handle) {
            $this->handle = Str::slug($this->title);
        }

        $data = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
        ];

        if ($this->collection?->exists) {
            $this->collection->update($data);
            $collection = $this->collection;
        } else {
            $collection = Collection::create($data);
        }

        $syncData = [];
        foreach ($this->assignedProductIds as $position => $productId) {
            $syncData[$productId] = ['position' => $position + 1];
        }
        $collection->products()->sync($syncData);

        $this->dispatch('toast', type: 'success', message: $this->collection?->exists
            ? __('Collection updated.')
            : __('Collection created.')
        );

        $this->redirect(route('admin.collections.edit', $collection), navigate: true);
    }

    #[Computed]
    public function searchResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (strlen($this->productSearch) < 2) {
            return \Illuminate\Database\Eloquent\Collection::make();
        }

        return Product::query()
            ->where('store_id', app('current_store')->id)
            ->where('title', 'like', "%{$this->productSearch}%")
            ->whereNotIn('id', $this->assignedProductIds)
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function assignedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($this->assignedProductIds)) {
            return \Illuminate\Database\Eloquent\Collection::make();
        }

        $products = Product::whereIn('id', $this->assignedProductIds)->get();

        return $products->sortBy(function ($product) {
            return array_search($product->id, $this->assignedProductIds);
        })->values();
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->collection?->exists ?? false;
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form');
    }
}
