<?php

namespace App\Livewire\Storefront\Search;

use App\Services\SearchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
#[Title('Search')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $sort = 'relevance';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $store = app('current_store');
        $products = null;

        if (strlen($this->q) >= 2) {
            $searchService = app(SearchService::class);

            try {
                $products = $searchService->search($store, $this->q);

                // Apply sorting after FTS5 search
                if ($this->sort !== 'relevance') {
                    $products = $this->applySorting($store, $this->q);
                }
            } catch (\Throwable) {
                // Fallback to LIKE query if FTS5 is not available
                $products = $this->fallbackSearch($store);
            }
        }

        return view('livewire.storefront.search.index', [
            'products' => $products,
        ]);
    }

    private function applySorting(\App\Models\Store $store, string $query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $searchService = app(SearchService::class);
        $sanitized = trim(preg_replace('/["\*\(\)\{\}\[\]\^~:]/u', ' ', $query));
        $words = array_filter(explode(' ', $sanitized));
        $ftsQuery = implode(' ', array_map(fn (string $w): string => '"'.$w.'"*', $words));

        $productQuery = \App\Models\Product::query()
            ->withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', \App\Enums\ProductStatus::Active)
            ->whereIn('products.id', function ($sub) use ($ftsQuery) {
                $sub->select('rowid')
                    ->from('products_fts')
                    ->whereRaw('products_fts MATCH ?', [$ftsQuery]);
            })
            ->with(['variants.inventoryItem', 'media']);

        $productQuery = match ($this->sort) {
            'price-asc' => $productQuery->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) ASC'),
            'price-desc' => $productQuery->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) DESC'),
            'title-asc' => $productQuery->orderBy('title', 'asc'),
            'newest' => $productQuery->orderBy('created_at', 'desc'),
            default => $productQuery,
        };

        return $productQuery->paginate(12);
    }

    private function fallbackSearch(\App\Models\Store $store): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = \App\Models\Product::where('store_id', $store->id)
            ->active()
            ->where('title', 'LIKE', '%'.$this->q.'%')
            ->with(['variants.inventoryItem', 'media']);

        $query = match ($this->sort) {
            'price-asc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) ASC'),
            'price-desc' => $query->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) DESC'),
            'title-asc' => $query->orderBy('title', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('title'),
        };

        return $query->paginate(12);
    }
}
