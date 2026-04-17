<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $query = '';

    public string $sort = 'relevance';

    public string $vendor = '';

    public ?int $priceMin = null;

    public ?int $priceMax = null;

    public function mount(): void
    {
        $this->query = request()->query('q', '');
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedVendor(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['vendor', 'priceMin', 'priceMax']);
        $this->resetPage();
    }

    public function render(): mixed
    {
        $results = null;
        $vendors = collect();

        if ($this->query && app()->bound('current_store')) {
            $store = app('current_store');

            if ($store instanceof Store) {
                $service = app(SearchService::class);

                $filters = array_filter([
                    'vendor' => $this->vendor ?: null,
                    'price_min' => $this->priceMin,
                    'price_max' => $this->priceMax,
                    'sort' => $this->sort,
                ]);

                $results = $service->search($store, $this->query, $filters, 24);

                // Get vendors from all matching products (without vendor filter)
                $vendors = Product::query()
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('status', 'active')
                    ->whereIn('id', $results->pluck('id'))
                    ->whereNotNull('vendor')
                    ->where('vendor', '!=', '')
                    ->distinct()
                    ->pluck('vendor')
                    ->sort()
                    ->values();
            }
        }

        return view('livewire.storefront.search.index', [
            'results' => $results,
            'vendors' => $vendors,
        ])->layout('layouts::storefront');
    }
}
