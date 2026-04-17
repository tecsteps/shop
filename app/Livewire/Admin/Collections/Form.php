<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
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
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->collection = $collection->load('products');
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = $collection->description_html ?? '';
            $this->status = $collection->status->value;
            $this->assignedProductIds = $collection->products
                ->sortBy('pivot.position')
                ->pluck('id')
                ->all();
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
        $this->assignedProductIds = array_values(
            array_filter($this->assignedProductIds, fn ($id) => $id !== $productId)
        );
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:active,archived,draft'],
        ]);

        DB::transaction(function () {
            if ($this->collection && $this->collection->exists) {
                $this->authorize('update', $this->collection);

                $this->collection->update([
                    'title' => $this->title,
                    'handle' => $this->handle ?: app(HandleGenerator::class)->generate(
                        $this->title,
                        'collections',
                        $this->collection->store_id,
                        $this->collection->id,
                    ),
                    'description_html' => $this->descriptionHtml ?: null,
                    'status' => $this->status,
                ]);
            } else {
                $this->authorize('create', Collection::class);

                $store = app('current_store');
                $this->collection = Collection::create([
                    'store_id' => $store->id,
                    'title' => $this->title,
                    'handle' => $this->handle ?: app(HandleGenerator::class)->generate(
                        $this->title,
                        'collections',
                        $store->id,
                    ),
                    'description_html' => $this->descriptionHtml ?: null,
                    'status' => CollectionStatus::from($this->status),
                ]);
            }

            // Sync products with position
            $syncData = [];
            foreach ($this->assignedProductIds as $position => $productId) {
                $syncData[$productId] = ['position' => $position + 1];
            }
            $this->collection->products()->sync($syncData);
        });

        $this->dispatch('toast', type: 'success', message: 'Collection saved successfully.');
        $this->redirect(route('admin.collections.edit', $this->collection), navigate: true);
    }

    #[Computed]
    public function searchResults(): EloquentCollection
    {
        if (strlen($this->productSearch) < 2) {
            return new EloquentCollection;
        }

        return Product::query()
            ->where('title', 'like', '%'.$this->productSearch.'%')
            ->whereNotIn('id', $this->assignedProductIds)
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function assignedProducts(): EloquentCollection
    {
        if (empty($this->assignedProductIds)) {
            return new EloquentCollection;
        }

        $products = Product::whereIn('id', $this->assignedProductIds)
            ->with(['media' => fn ($q) => $q->orderBy('position')->limit(1)])
            ->get();

        return $products->sortBy(function ($product) {
            return array_search($product->id, $this->assignedProductIds);
        })->values();
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->collection !== null && $this->collection->exists;
    }

    public function render()
    {
        return view('livewire.admin.collections.form')
            ->layout('layouts.admin', ['title' => $this->isEditing ? 'Edit Collection' : 'Add Collection']);
    }
}
