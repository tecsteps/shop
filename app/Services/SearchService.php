<?php

namespace App\Services;

use App\Enums\CollectionStatus;
use App\Enums\MediaStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * Full-text product search over a SQLite FTS5 index (spec 05 §16).
 *
 * The index is kept in sync by App\Observers\ProductObserver; visibility
 * rules (active + published) are applied at query time, not at index time.
 */
class SearchService
{
    /**
     * Search visible products of the store (spec 05 §16.3, spec 02 §2.5).
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(
        Store $store,
        string $query,
        array $filters = [],
        int $perPage = 24,
        string $sort = 'relevance',
        int $page = 1,
    ): LengthAwarePaginator {
        $matches = $this->matchingProductIds($store, $query);

        $products = $this->baseQuery($store, $matches);

        $this->applyFilters($products, $filters);
        $this->applySort($products, $sort, $matches);

        $paginator = $products
            ->with(['variants.inventoryItem', 'media'])
            ->paginate(max(1, $perPage), ['products.*'], 'page', max(1, $page));

        $this->logQuery($store, $query, $filters, $paginator->total());

        return $paginator;
    }

    /**
     * Autocomplete suggestions: products and collections matching the prefix,
     * backfilled with popular past queries (spec 02 §2.5, spec 04 §11.1).
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): SupportCollection
    {
        $limit = max(1, $limit);

        if ($this->sanitizeQuery($prefix) === '') {
            return collect();
        }

        $matches = $this->matchingProductIds($store, $prefix);

        $productsQuery = $this->baseQuery($store, $matches);
        $this->orderByRank($productsQuery, $matches);

        $products = $productsQuery
            ->with(['variants', 'media'])
            ->limit($limit)
            ->get();

        $suggestions = $products->map(fn (Product $product): array => $this->productSuggestion($store, $product));

        $collections = Collection::query()
            ->where('store_id', $store->id)
            ->where('status', CollectionStatus::Active)
            ->where('title', 'like', $this->likePrefix($prefix).'%')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (Collection $collection): array => [
                'type' => 'collection',
                'title' => $collection->title,
                'handle' => $collection->handle,
                'image_url' => null,
            ]);

        $suggestions = $suggestions->concat($collections)->values();

        // Backfill with popular past queries when products/collections are scarce.
        if ($suggestions->count() < $limit) {
            $pastQueries = SearchQuery::query()
                ->where('store_id', $store->id)
                ->where('query', 'like', $this->likePrefix($prefix).'%')
                ->groupBy('query')
                ->orderByRaw('COUNT(*) DESC')
                ->orderByRaw('MAX(created_at) DESC')
                ->limit($limit - $suggestions->count())
                ->pluck('query')
                ->map(fn (string $query): array => [
                    'type' => 'query',
                    'title' => $query,
                    'handle' => null,
                    'image_url' => null,
                ]);

            $suggestions = $suggestions->concat($pastQueries)->values();
        }

        return $suggestions;
    }

    /**
     * Facets of the full matched (visible) result set (spec 02 §2.5).
     *
     * @return array{vendors: list<array{value: string, count: int}>, tags: list<array{value: string, count: int}>, price_range: array{min: int|null, max: int|null}}
     */
    public function facets(Store $store, string $query): array
    {
        $matches = $this->matchingProductIds($store, $query);
        $base = $this->baseQuery($store, $matches);

        $vendors = (clone $base)
            ->whereNotNull('products.vendor')
            ->groupBy('products.vendor')
            ->selectRaw('products.vendor, COUNT(*) as aggregate')
            ->orderBy('products.vendor')
            ->pluck('aggregate', 'products.vendor')
            ->map(fn ($count, $vendor): array => ['value' => $vendor, 'count' => (int) $count])
            ->values()
            ->all();

        $tags = (clone $base)
            ->pluck('products.tags')
            ->flatMap(fn ($tags): array => is_array($tags) ? $tags : (json_decode($tags ?? '[]', true) ?: []))
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $tag): array => ['value' => $tag, 'count' => (int) $count])
            ->values()
            ->all();

        $priceRange = DB::table('product_variants')
            ->whereIn('product_id', (clone $base)->select('products.id'))
            ->selectRaw('MIN(price_amount) as min_price, MAX(price_amount) as max_price')
            ->first();

        return [
            'vendors' => $vendors,
            'tags' => $tags,
            'price_range' => [
                'min' => $priceRange?->min_price !== null ? (int) $priceRange->min_price : null,
                'max' => $priceRange?->max_price !== null ? (int) $priceRange->max_price : null,
            ],
        ];
    }

    /**
     * Insert or replace the product's FTS row (spec 05 §16.2).
     */
    public function syncProduct(Product $product): void
    {
        DB::delete('DELETE FROM products_fts WHERE product_id = ?', [$product->id]);

        DB::insert(
            'INSERT INTO products_fts (store_id, product_id, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $product->store_id,
                $product->id,
                $product->title ?? '',
                strip_tags($product->description_html ?? ''),
                $product->vendor ?? '',
                $product->product_type ?? '',
                implode(' ', $product->tags ?? []),
            ],
        );
    }

    /**
     * Remove the product from the FTS index (spec 05 §16.2).
     */
    public function removeProduct(int $productId): void
    {
        DB::delete('DELETE FROM products_fts WHERE product_id = ?', [$productId]);
    }

    /**
     * Rebuild the store's FTS index from scratch. Returns the indexed count.
     */
    public function reindex(Store $store): int
    {
        DB::delete('DELETE FROM products_fts WHERE store_id = ?', [$store->id]);

        $count = 0;

        Product::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->chunkById(200, function ($products) use (&$count): void {
                foreach ($products as $product) {
                    $this->syncProduct($product);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Strip everything that is not a Unicode letter, number, or whitespace
     * (spec 06 §4.6) and collapse runs of whitespace.
     */
    public function sanitizeQuery(string $query): string
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $query) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $clean) ?? '');
    }

    /**
     * Base query over matched products: store-scoped, visible, ranked.
     *
     * @param  array<int, float>  $matches
     * @return Builder<Product>
     */
    private function baseQuery(Store $store, array $matches): Builder
    {
        return Product::query()
            ->where('products.store_id', $store->id)
            ->visible()
            ->whereIn('products.id', array_keys($matches));
    }

    /**
     * Run the FTS5 MATCH query and return [product_id => rank] pairs.
     *
     * @return array<int, float>
     */
    private function matchingProductIds(Store $store, string $query): array
    {
        $matchExpression = $this->buildMatchExpression($store, $query);

        if ($matchExpression === null) {
            return [];
        }

        $rows = DB::select(
            'SELECT product_id, rank FROM products_fts WHERE products_fts MATCH ? AND store_id = ? ORDER BY rank',
            [$matchExpression, $store->id],
        );

        $matches = [];

        foreach ($rows as $row) {
            $matches[(int) $row->product_id] = (float) $row->rank;
        }

        return $matches;
    }

    /**
     * Build a safe FTS5 MATCH expression from the user query:
     * sanitized, stop words removed, synonyms expanded (OR), every token
     * double-quoted (neutralizes FTS5 operators), prefix '*' on the last
     * token (spec 05 §16.3, spec 06 §4.6).
     */
    private function buildMatchExpression(Store $store, string $query): ?string
    {
        $tokens = explode(' ', $this->sanitizeQuery($query));
        $tokens = array_values(array_filter($tokens, fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return null;
        }

        $stopWords = $this->stopWords($store);
        $tokens = array_values(array_filter(
            $tokens,
            fn (string $token): bool => ! in_array(mb_strtolower($token), $stopWords, true),
        ));

        if ($tokens === []) {
            return null;
        }

        $clauses = [];

        foreach ($tokens as $index => $token) {
            $alternatives = $this->synonymsFor($store, $token);
            $terms = array_map(fn (string $term): string => '"'.$term.'"', $alternatives);

            if ($index === array_key_last($tokens)) {
                // Prefix matching on the typed token ("running sh" -> "running" "sh" *).
                $terms[0] = '"'.$token.'" *';
            }

            $clauses[] = count($terms) === 1 ? $terms[0] : '('.implode(' OR ', $terms).')';
        }

        return implode(' ', $clauses);
    }

    /**
     * The store's configured stop words, lowercased.
     *
     * @return list<string>
     */
    private function stopWords(Store $store): array
    {
        $settings = SearchSettings::query()->find($store->id);

        return array_map(
            fn ($word): string => mb_strtolower(trim((string) $word)),
            $settings?->stop_words_json ?? [],
        );
    }

    /**
     * All terms of every synonym group containing the token (the token
     * itself first). Terms are sanitized so they are safe to quote.
     *
     * @return list<string>
     */
    private function synonymsFor(Store $store, string $token): array
    {
        $settings = SearchSettings::query()->find($store->id);
        $groups = $settings?->synonyms_json ?? [];

        $terms = [$token];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $normalized = array_map(fn ($term): string => mb_strtolower(trim((string) $term)), $group);

            if (in_array(mb_strtolower($token), $normalized, true)) {
                foreach ($group as $term) {
                    $sanitized = $this->sanitizeQuery((string) $term);

                    if ($sanitized !== '' && ! in_array($sanitized, $terms, true)) {
                        $terms[] = $sanitized;
                    }
                }
            }
        }

        return $terms;
    }

    /**
     * Apply search filters (spec 02 §2.5 filters schema).
     *
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', fn ($q) => $q->where('collections.id', (int) $filters['collection_id']));
        }

        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $query->whereHas('variants', fn ($q) => $q->where('price_amount', '>=', (int) $filters['price_min']));
        }

        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $query->whereHas('variants', fn ($q) => $q->where('price_amount', '<=', (int) $filters['price_max']));
        }

        if (! empty($filters['in_stock'])) {
            $query->whereHas('variants.inventoryItem', fn ($q) => $q->whereRaw('(quantity_on_hand - quantity_reserved) > 0'));
        }

        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('products.tags', $tag);
            }
        }

        if (! empty($filters['vendor'])) {
            $vendors = is_array($filters['vendor']) ? $filters['vendor'] : [$filters['vendor']];
            $query->whereIn('products.vendor', $vendors);
        }
    }

    /**
     * Apply the requested sort order (spec 02 §2.5).
     *
     * @param  Builder<Product>  $query
     * @param  array<int, float>  $matches
     */
    private function applySort(Builder $query, string $sort, array $matches): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy($this->minimumPriceSubquery()),
            'price_desc' => $query->orderByDesc($this->minimumPriceSubquery()),
            'newest' => $query->orderByDesc('products.published_at'),
            'best_selling' => $query->orderByDesc($this->salesCountSubquery()),
            default => $this->orderByRank($query, $matches),
        };
    }

    /**
     * Order by FTS5 bm25 rank (lower is more relevant).
     *
     * @param  Builder<Product>  $query
     * @param  array<int, float>  $matches
     */
    private function orderByRank(Builder $query, array $matches): void
    {
        if ($matches === []) {
            return;
        }

        $cases = collect($matches)
            ->map(fn (float $rank, int $id): string => 'WHEN '.$id.' THEN '.$rank)
            ->implode(' ');

        $query->orderByRaw("CASE products.id {$cases} ELSE 0 END");
    }

    /**
     * Subquery selecting the minimum variant price of a product.
     */
    private function minimumPriceSubquery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return \App\Models\ProductVariant::query()
            ->selectRaw('MIN(price_amount)')
            ->whereColumn('product_variants.product_id', 'products.id');
    }

    /**
     * Subquery selecting the total sold quantity of a product.
     */
    private function salesCountSubquery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('order_lines')
            ->selectRaw('COALESCE(SUM(quantity), 0)')
            ->whereColumn('order_lines.product_id', 'products.id');
    }

    /**
     * Shape a product into an autocomplete suggestion.
     *
     * @return array<string, mixed>
     */
    private function productSuggestion(Store $store, Product $product): array
    {
        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
        $image = $product->media->firstWhere('status', MediaStatus::Ready);

        return [
            'type' => 'product',
            'title' => $product->title,
            'handle' => $product->handle,
            'image_url' => $image?->url(),
            'price_amount' => $product->variants->min('price_amount'),
            'currency' => $variant?->currency ?? $store->default_currency,
        ];
    }

    /**
     * Escape LIKE wildcards in a user-entered prefix.
     */
    private function likePrefix(string $prefix): string
    {
        return str_replace(['%', '_'], '', trim($prefix));
    }

    /**
     * Log the search for analytics and autocomplete backfill (spec 05 §16.4).
     *
     * @param  array<string, mixed>  $filters
     */
    private function logQuery(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::create([
            'store_id' => $store->id,
            'query' => trim($query),
            'filters_json' => $filters !== [] ? $filters : null,
            'results_count' => $resultsCount,
        ]);
    }
}
