<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    public ?Collection $collection = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:255')]
    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /** @var array<int> */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = $collection->description_html ?? '';
            $this->status = $collection->status ?? 'active';
            $this->assignedProductIds = $collection->products()->orderByPivot('position')->pluck('products.id')->toArray();
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->collection) {
            $this->handle = Str::slug($this->title);
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
        $this->validate();

        $data = [
            'store_id' => session('store_id'),
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
        ];

        if ($this->collection) {
            $this->collection->update($data);
        } else {
            $this->collection = Collection::withoutGlobalScopes()->create($data);
        }

        $sync = [];
        foreach ($this->assignedProductIds as $position => $productId) {
            $sync[$productId] = ['position' => $position];
        }
        $this->collection->products()->sync($sync);

        $this->dispatch('toast', type: 'success', message: 'Collection saved.');

        if ($this->collection->wasRecentlyCreated) {
            $this->redirect(route('admin.collections.edit', $this->collection), navigate: true);
        }
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->productSearch) < 2) {
            return collect();
        }

        return Product::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->where('title', 'like', "%{$this->productSearch}%")
            ->whereNotIn('id', $this->assignedProductIds)
            ->limit(10)
            ->get();
    }

    public function getAssignedProductsProperty()
    {
        if (empty($this->assignedProductIds)) {
            return collect();
        }

        $products = Product::withoutGlobalScopes()
            ->whereIn('id', $this->assignedProductIds)
            ->get()
            ->keyBy('id');

        return collect($this->assignedProductIds)->map(fn ($id) => $products[$id] ?? null)->filter();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.collections.form');
    }
}
