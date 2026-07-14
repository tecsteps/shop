<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SearchService
{
    /** @param array<string, mixed> $filters */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        return $this->searchWithFacets($store, $query, $filters, 'relevance', $perPage)['paginator'];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{paginator: LengthAwarePaginator, facets: array<string, mixed>}
     */
    public function searchWithFacets(Store $store, string $query, array $filters = [], string $sort = 'relevance', int $perPage = 24): array
    {
        $query = trim($query);
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $searchTerms = $this->searchTerms($store, $query);
        $ids = $this->matchingIds($store, $searchTerms);
        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->when($ids !== null, fn ($builder) => $builder->whereIn('id', $ids))
            ->when($ids === null && $searchTerms !== [], fn ($builder) => $builder->where(function ($nested) use ($searchTerms): void {
                foreach ($searchTerms as $termGroup) {
                    $nested->where(function ($termQuery) use ($termGroup): void {
                        foreach ($termGroup as $term) {
                            $termQuery->orWhere('title', 'like', "%{$term}%")
                                ->orWhere('description_html', 'like', "%{$term}%")
                                ->orWhere('vendor', 'like', "%{$term}%")
                                ->orWhere('product_type', 'like', "%{$term}%");
                        }
                    });
                }
            }))
            ->when(isset($filters['vendor']), fn ($builder) => $builder->where('vendor', $filters['vendor']))
            ->when(isset($filters['collection_id']), fn ($builder) => $builder->whereHas('collections', fn ($collectionQuery) => $collectionQuery
                ->where('collections.id', (int) $filters['collection_id'])
                ->where('collections.store_id', $store->id)))
            ->when(isset($filters['price_min']), fn ($builder) => $builder->whereHas('variants', fn ($variantQuery) => $variantQuery->where('price_amount', '>=', (int) $filters['price_min'])))
            ->when(isset($filters['price_max']), fn ($builder) => $builder->whereHas('variants', fn ($variantQuery) => $variantQuery->where('price_amount', '<=', (int) $filters['price_max'])))
            ->when(($filters['in_stock'] ?? false) === true, fn ($builder) => $builder->whereHas('variants.inventoryItem', fn ($inventoryQuery) => $inventoryQuery
                ->where(fn ($available) => $available->where('policy', 'continue')->orWhereColumn('quantity_on_hand', '>', 'quantity_reserved'))))
            ->when(isset($filters['tags']), function ($builder) use ($filters): void {
                foreach ((array) $filters['tags'] as $tag) {
                    $builder->whereJsonContains('tags', (string) $tag);
                }
            })
            ->with(['variants.inventoryItem', 'media'])
            ->withSum('orderLines as units_sold', 'quantity')
            ->get();

        if ($sort === 'relevance' && $ids !== null) {
            $order = array_flip($ids);
            $products = $products->sortBy(fn (Product $product): int => $order[$product->id] ?? PHP_INT_MAX)->values();
        } elseif ($sort === 'price_asc') {
            $products = $products->sortBy(fn (Product $product): int => (int) ($product->variants->min('price_amount') ?? PHP_INT_MAX))->values();
        } elseif ($sort === 'price_desc') {
            $products = $products->sortByDesc(fn (Product $product): int => (int) ($product->variants->min('price_amount') ?? 0))->values();
        } elseif ($sort === 'newest') {
            $products = $products->sortByDesc('published_at')->values();
        } elseif ($sort === 'best_selling') {
            $products = $products->sortByDesc(fn (Product $product): int => (int) ($product->units_sold ?? 0))->values();
        }
        $total = $products->count();
        $items = $products->forPage($page, $perPage)->values();

        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => $filters,
            'results_count' => $total,
        ]);

        return [
            'paginator' => new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]),
            'facets' => $this->facets($products),
        ];
    }

    /** @return Collection<int, Product> */
    public function autocomplete(Store $store, string $prefix, int $limit = 8): Collection
    {
        $results = $this->search($store, $prefix, [], $limit);

        return collect($results->items());
    }

    /** @return Collection<int, array<string, mixed>> */
    public function suggestions(Store $store, string $prefix, int $limit = 5): Collection
    {
        $products = collect($this->searchWithFacets($store, $prefix, [], 'relevance', $limit)['paginator']->items())
            ->map(fn (Product $product): array => ['type' => 'product', 'model' => $product]);
        $remaining = max(0, $limit - $products->count());
        $collections = \App\Models\Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->where('title', 'like', '%'.trim($prefix).'%')
            ->limit($remaining)
            ->get()
            ->map(fn ($collection): array => ['type' => 'collection', 'model' => $collection]);

        return $products->concat($collections)->take($limit)->values();
    }

    public function syncProduct(Product $product): void
    {
        try {
            DB::table('products_fts')->where('product_id', $product->id)->delete();
            DB::table('products_fts')->insert([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'title' => $product->title,
                'description' => strip_tags((string) $product->description_html),
                'vendor' => (string) $product->vendor,
                'product_type' => (string) $product->product_type,
                'tags' => implode(' ', (array) $product->tags),
            ]);
        } catch (Throwable) {
            // FTS5 can be unavailable in minimal SQLite builds; LIKE remains a safe fallback.
        }
    }

    public function removeProduct(int $productId): void
    {
        try {
            DB::table('products_fts')->where('product_id', $productId)->delete();
        } catch (Throwable) {
            // See syncProduct fallback.
        }
    }

    /** @return list<int>|null */
    private function matchingIds(Store $store, array $termGroups): ?array
    {
        if ($termGroups === []) {
            return null;
        }

        try {
            $match = implode(' ', array_map(function (array $terms): string {
                $escaped = array_map(fn (string $term): string => '"'.str_replace('"', '""', $term).'"*', $terms);

                return count($escaped) === 1 ? $escaped[0] : '('.implode(' OR ', $escaped).')';
            }, $termGroups));
            if ($match === '') {
                return [];
            }

            $rows = DB::select('SELECT product_id FROM products_fts WHERE products_fts MATCH ? AND store_id = ? ORDER BY rank', [$match, $store->id]);

            return array_map(fn (object $row): int => (int) $row->product_id, $rows);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return list<list<string>> */
    private function searchTerms(Store $store, string $query): array
    {
        $settings = SearchSettings::withoutGlobalScopes()->where('store_id', $store->id)->first();
        $stopWords = collect((array) $settings?->stop_words_json)->map(fn ($word): string => mb_strtolower(trim((string) $word)))->filter();
        $synonyms = collect((array) $settings?->synonyms_json)->map(fn ($group) => collect((array) $group)
            ->map(fn ($word): string => mb_strtolower(trim((string) $word)))->filter()->values()->all());
        $tokens = preg_split('/\s+/', mb_strtolower(preg_replace('/[^\pL\pN\s-]+/u', ' ', $query) ?: '')) ?: [];

        return collect($tokens)->map(fn (string $token): string => trim($token))->filter()
            ->reject(fn (string $token): bool => $stopWords->contains($token))
            ->map(function (string $token) use ($synonyms): array {
                $group = $synonyms->first(fn (array $words): bool => in_array($token, $words, true));

                return $group === null ? [$token] : array_values(array_unique([$token, ...$group]));
            })->values()->all();
    }

    /** @param Collection<int, Product> $products */
    private function facets(Collection $products): array
    {
        $prices = $products->flatMap(fn (Product $product) => $product->variants->pluck('price_amount'))->map(fn ($price): int => (int) $price);

        return [
            'vendors' => $products->pluck('vendor')->filter()->countBy()->map(fn (int $count, string $value): array => compact('value', 'count'))->values()->all(),
            'tags' => $products->flatMap(fn (Product $product): array => (array) $product->tags)->countBy()->map(fn (int $count, string $value): array => compact('value', 'count'))->values()->all(),
            'price_range' => ['min' => $prices->isEmpty() ? null : $prices->min(), 'max' => $prices->isEmpty() ? null : $prices->max()],
        ];
    }
}
