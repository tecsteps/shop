<?php

namespace App\Livewire\Storefront\Search;

use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Services\SearchService;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    use ResolvesStore;

    #[Url('q')]
    public string $query = '';

    #[Url]
    public string $sort = 'relevance';

    #[Url]
    public bool $inStock = false;

    public int $page = 1;

    public function mount(): void
    {
        $this->query = (string) request()->query('q', '');
    }

    public function updatedQuery(): void
    {
        $this->page = 1;
    }

    public function updatedSort(): void
    {
        $this->page = 1;
    }

    public function updatedInStock(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function search(): void
    {
        $this->page = 1;
    }

    public function render()
    {
        $store = $this->store();
        $result = ['results' => null, 'facets' => ['vendors' => [], 'tags' => [], 'price_range' => ['min' => 0, 'max' => 0]]];

        if (trim($this->query) !== '') {
            $filters = [];

            if ($this->inStock) {
                $filters['in_stock'] = true;
            }

            $result = app(SearchService::class)->search($store, $this->query, $filters, $this->sort, 12, $this->page);
        }

        return view('livewire.storefront.search.index', [
            'store' => $store,
            'paginator' => $result['results'],
            'facets' => $result['facets'],
        ])->layout('storefront.layouts.app', [
            'title' => 'Search results - '.$store->name,
            'metaDescription' => 'Search results at '.$store->name,
        ]);
    }
}
