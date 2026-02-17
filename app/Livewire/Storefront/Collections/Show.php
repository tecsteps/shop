<?php

namespace App\Livewire\Storefront\Collections;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use WithPagination;

    public string $handle = '';

    public string $collectionTitle = '';

    #[Url]
    public string $sort = 'featured';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $collection = null;
        $products = collect();

        if (app()->bound('current_store')) {
            $collection = \App\Models\Collection::withoutGlobalScopes()
                ->where('store_id', app('current_store')->id)
                ->where('handle', $this->handle)
                ->where('status', 'active')
                ->first();

            if ($collection) {
                $this->collectionTitle = $collection->title;

                $query = $collection->products()
                    ->where('products.status', 'active')
                    ->with(['variants.inventoryItem', 'media']);

                $query = match ($this->sort) {
                    'price-asc' => $query->orderBy('products.id'),
                    'price-desc' => $query->orderByDesc('products.id'),
                    'newest' => $query->orderByDesc('products.created_at'),
                    'title-asc' => $query->orderBy('products.title'),
                    default => $query->orderBy('collection_products.position'),
                };

                $products = $query->paginate(12);
            }
        }

        if (! $collection) {
            abort(404);
        }

        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');

        return view('livewire.storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
        ])->title("{$collection->title} - {$storeName}");
    }
}
