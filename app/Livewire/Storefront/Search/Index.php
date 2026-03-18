<?php

namespace App\Livewire\Storefront\Search;

use App\Services\AnalyticsService;
use App\Services\SearchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Search')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    #[Url]
    public string $sort = 'relevance';

    #[Url]
    public ?string $vendor = null;

    #[Url]
    public ?int $minPrice = null;

    #[Url]
    public ?int $maxPrice = null;

    #[Url]
    public ?int $collectionId = null;

    #[Computed]
    public function results(): LengthAwarePaginator
    {
        if (trim($this->query) === '') {
            return new LengthAwarePaginator([], 0, 24);
        }

        $store = app('current_store');
        $service = app(SearchService::class);

        return $service->search($store, $this->query, [
            'sort' => $this->sort,
            'vendor' => $this->vendor,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'collection_id' => $this->collectionId,
        ]);
    }

    #[Computed]
    public function availableVendors(): array
    {
        $store = app('current_store');

        return $store->products()
            ->where('status', 'active')
            ->whereNotNull('vendor')
            ->distinct()
            ->pluck('vendor')
            ->sort()
            ->values()
            ->all();
    }

    #[Computed]
    public function availableCollections(): \Illuminate\Support\Collection
    {
        $store = app('current_store');

        return $store->collections()
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function clearFilters(): void
    {
        $this->reset(['vendor', 'minPrice', 'maxPrice', 'collectionId']);
        $this->resetPage();
    }

    public function updatedQuery(): void
    {
        $this->resetPage();

        if (trim($this->query) !== '') {
            $store = app('current_store');
            app(AnalyticsService::class)->track($store, 'search', [
                'query' => $this->query,
            ], session()->getId());
        }
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.storefront.search.index')
            ->layout('storefront.layouts.app', ['title' => 'Search']);
    }
}
