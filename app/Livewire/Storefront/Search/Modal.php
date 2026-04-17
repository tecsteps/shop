<?php

namespace App\Livewire\Storefront\Search;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Store;
use App\Services\SearchService;
use Livewire\Attributes\On;
use Livewire\Component;

class Modal extends Component
{
    public string $query = '';

    public bool $open = false;

    /** @var array<int, array{id: int, title: string, handle: string, price: int, image: string|null}> */
    public array $productResults = [];

    /** @var array<int, array{id: int, title: string, handle: string}> */
    public array $collectionResults = [];

    public bool $isSearching = false;

    public bool $hasSearched = false;

    #[On('open-search-modal')]
    public function openModal(): void
    {
        $this->open = true;
        $this->reset('query', 'productResults', 'collectionResults', 'hasSearched');
    }

    #[On('close-search-modal')]
    public function closeModal(): void
    {
        $this->open = false;
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) < 2) {
            $this->productResults = [];
            $this->collectionResults = [];
            $this->hasSearched = false;

            return;
        }

        $this->performSearch();
    }

    protected function performSearch(): void
    {
        $store = $this->getStore();
        if (! $store) {
            return;
        }

        $searchService = app(SearchService::class);

        $products = $searchService->autocomplete($store, $this->query, 5);

        $this->productResults = $products->map(fn ($product) => [
            'id' => $product->id,
            'title' => $product->title,
            'handle' => $product->handle,
            'price' => $product->variants->first()?->price_amount ?? 0,
            'image' => $product->media->first()?->url ?? null,
        ])->all();

        $this->collectionResults = $this->searchCollections($store, $this->query);

        $this->hasSearched = true;
    }

    /**
     * @return array<int, array{id: int, title: string, handle: string}>
     */
    protected function searchCollections(Store $store, string $query): array
    {
        return Collection::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', CollectionStatus::Active)
            ->where('title', 'LIKE', '%'.$query.'%')
            ->limit(5)
            ->get()
            ->map(fn ($collection) => [
                'id' => $collection->id,
                'title' => $collection->title,
                'handle' => $collection->handle,
            ])
            ->all();
    }

    protected function getStore(): ?Store
    {
        if (app()->bound('current_store')) {
            return app('current_store');
        }

        return null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.search.modal');
    }
}
