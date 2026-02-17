<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $sort = 'relevance';

    #[Url]
    public string $vendor = '';

    #[Url]
    public string $priceMin = '';

    #[Url]
    public string $priceMax = '';

    #[Url]
    public string $collectionId = '';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedVendor(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMin(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMax(): void
    {
        $this->resetPage();
    }

    public function updatedCollectionId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->vendor = '';
        $this->priceMin = '';
        $this->priceMax = '';
        $this->collectionId = '';
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $products = collect();
        $vendors = collect();
        $collections = collect();

        if (app()->bound('current_store') && $this->q !== '') {
            $store = app('current_store');
            $searchService = app(SearchService::class);

            $filters = array_filter([
                'vendor' => $this->vendor ?: null,
                'price_min' => $this->priceMin !== '' ? (int) $this->priceMin : null,
                'price_max' => $this->priceMax !== '' ? (int) $this->priceMax : null,
                'collection_id' => $this->collectionId !== '' ? (int) $this->collectionId : null,
            ]);

            $products = $searchService->search($store, $this->q, $filters);

            $vendors = \App\Models\Product::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', 'active')
                ->whereNotNull('vendor')
                ->distinct()
                ->pluck('vendor')
                ->sort()
                ->values();

            $collections = \App\Models\Collection::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('status', 'active')
                ->orderBy('title')
                ->get(['id', 'title']);
        }

        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');

        return view('livewire.storefront.search.index', [
            'products' => $products,
            'vendors' => $vendors,
            'collections' => $collections,
        ])->title("Search results - {$storeName}");
    }
}
