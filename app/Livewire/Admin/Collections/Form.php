<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /**
     * @var array<int, int>
     */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection?->exists) {
            $store = app('current_store');

            abort_unless($store instanceof Store && (int) $collection->store_id === $store->getKey(), 404);

            $this->collection = $collection->load('products');
            $this->fillFromCollection($this->collection);
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
            fn (int $assignedProductId): bool => $assignedProductId !== $productId,
        ));
    }

    public function save(): void
    {
        $store = app('current_store');
        abort_unless($store instanceof Store, 404);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collections', 'handle')
                    ->where('store_id', $store->getKey())
                    ->ignore($this->collection?->getKey()),
            ],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        ]);

        $attributes = [
            'store_id' => $store->getKey(),
            'title' => $this->title,
            'handle' => Str::slug($this->handle),
            'description_html' => $this->descriptionHtml ?: null,
            'type' => 'manual',
            'status' => $this->status,
        ];

        $collection = $this->collection instanceof Collection
            ? tap($this->collection)->update($attributes)
            : Collection::query()->create($attributes);

        $collection->products()->sync(collect($this->assignedProductIds)
            ->values()
            ->mapWithKeys(fn (int $productId, int $position): array => [$productId => ['position' => $position]])
            ->all());

        $this->collection = $collection->refresh()->load('products');
        $this->fillFromCollection($this->collection);

        session()->flash('status', 'Collection saved');
        $this->dispatch('toast', type: 'success', message: __('Collection saved'));
    }

    public function searchResults(): SupportCollection
    {
        if (trim($this->productSearch) === '') {
            return collect();
        }

        return Product::query()
            ->whereNotIn('id', $this->assignedProductIds)
            ->where(function (Builder $query): void {
                $query
                    ->where('title', 'like', '%'.$this->productSearch.'%')
                    ->orWhere('handle', 'like', '%'.$this->productSearch.'%');
            })
            ->limit(5)
            ->get();
    }

    public function assignedProducts(): SupportCollection
    {
        if ($this->assignedProductIds === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $this->assignedProductIds)
            ->get()
            ->sortBy(fn (Product $product): int => array_search($product->getKey(), $this->assignedProductIds, true))
            ->values();
    }

    public function render(): mixed
    {
        return view('livewire.admin.collections.form', [
            'searchResults' => $this->searchResults(),
            'assignedProducts' => $this->assignedProducts(),
            'isEditing' => $this->collection !== null,
        ])->layout('layouts.app', [
            'title' => $this->collection ? $this->collection->title : __('Add collection'),
        ]);
    }

    private function fillFromCollection(Collection $collection): void
    {
        $this->title = $collection->title;
        $this->handle = $collection->handle;
        $this->descriptionHtml = (string) $collection->description_html;
        $this->status = $collection->status->value;
        $this->assignedProductIds = $collection->products->pluck('id')->map(fn (int $id): int => $id)->all();
    }
}
