<?php

namespace App\Livewire\Storefront\Search;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $query = '';

    #[Url]
    public string $sort = 'relevance';

    #[Url]
    public string $vendor = '';

    #[Url]
    public ?int $priceMin = null;

    #[Url]
    public ?int $priceMax = null;

    #[Url]
    public ?int $collection = null;

    public string $autocompleteQuery = '';

    /** @var array<int, array{id: int, title: string, handle: string}> */
    public array $suggestions = [];

    public bool $showSuggestions = false;

    public function updatedQuery(): void
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

    public function updatedCollection(): void
    {
        $this->resetPage();
    }

    public function updatedAutocompleteQuery(): void
    {
        if (mb_strlen($this->autocompleteQuery) < 2) {
            $this->suggestions = [];
            $this->showSuggestions = false;

            return;
        }

        $store = app('current_store');
        $searchService = app(SearchService::class);
        $this->suggestions = $searchService->autocomplete($store, $this->autocompleteQuery, 5)->all();
        $this->showSuggestions = count($this->suggestions) > 0;
    }

    public function selectSuggestion(string $handle): void
    {
        $this->showSuggestions = false;
        $this->redirectRoute('storefront.products.show', ['handle' => $handle]);
    }

    public function submitSearch(): void
    {
        $this->query = $this->autocompleteQuery;
        $this->showSuggestions = false;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->vendor = '';
        $this->priceMin = null;
        $this->priceMax = null;
        $this->collection = null;
        $this->sort = 'relevance';
        $this->resetPage();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function vendors(): array
    {
        $store = app('current_store');

        return Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNotNull('vendor')
            ->where('vendor', '!=', '')
            ->distinct()
            ->pluck('vendor')
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    #[Computed]
    public function collections(): \Illuminate\Database\Eloquent\Collection
    {
        return Collection::query()
            ->where('status', CollectionStatus::Active)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        if ($this->query === '') {
            return Product::query()->where('id', 0)->paginate(12);
        }

        $store = app('current_store');
        $searchService = app(SearchService::class);

        return $searchService->search($store, $this->query, [
            'vendor' => $this->vendor,
            'priceMin' => $this->priceMin,
            'priceMax' => $this->priceMax,
            'collection' => $this->collection,
        ], 12, $this->sort);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.search.index')
            ->layout('layouts.storefront', ['title' => 'Search']);
    }
}
