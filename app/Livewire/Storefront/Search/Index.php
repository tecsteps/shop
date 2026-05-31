<?php

namespace App\Livewire\Storefront\Search;

use App\Models\Product;
use App\Services\SearchService;
use App\Support\Storefront\ProductCardPresenter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Full search results page: product grid driven by a query string.
 *
 * Mirrors the collection page layout but is backed by the {@see SearchService}
 * FTS5 search (platform/#8) instead of a collection. Results are presented
 * through {@see ProductCardPresenter}, exactly like {@see \App\Livewire\Storefront\Collections\Show},
 * so the card markup stays consistent across the storefront.
 */
#[Layout('storefront.layouts.app')]
#[Title('Search')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    #[Url(as: 'sort')]
    public string $sort = 'relevance';

    public int $perPage = 24;

    /**
     * Reset pagination when the query or sort changes.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['query', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function render(SearchService $search)
    {
        $query = trim($this->query);

        if ($query === '') {
            return view('livewire.storefront.search.index', [
                'results' => collect(),
                'total' => 0,
                'paginator' => null,
            ]);
        }

        $paginator = $search->search(
            app('current_store'),
            $query,
            ['sort' => $this->sort],
            $this->perPage,
            $this->getPage(),
        );

        $cards = $paginator->getCollection()
            ->map(fn (Product $product): array => ProductCardPresenter::fromProduct($product));

        return view('livewire.storefront.search.index', [
            'results' => $cards,
            'total' => $paginator->total(),
            'paginator' => $paginator,
        ]);
    }
}
