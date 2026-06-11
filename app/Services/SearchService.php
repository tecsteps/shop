<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SQLite FTS5 product search (spec 05 section 16). The products_fts virtual
 * table mirrors searchable product data using the product id as its rowid;
 * queries MATCH against it, scope by store, and hydrate Product models.
 */
class SearchService
{
    /**
     * Minimum prefix length before autocomplete returns results.
     */
    public const int MIN_PREFIX_LENGTH = 2;

    /**
     * Cache key prefix for the per-store "last indexed" timestamp.
     */
    public const string REINDEXED_AT_CACHE_KEY = 'search_reindexed_at';

    /**
     * Per-request memoized search settings keyed by store id.
     *
     * @var array<int, SearchSettings|null>
     */
    protected array $settingsByStore = [];

    public function __construct(protected AnalyticsService $analytics) {}

    /**
     * Full search with filters, sorting, and pagination. Every search is
     * logged to search_queries and tracked as a "search" analytics event.
     *
     * @param  array{vendor?: string|null, vendors?: list<string>, product_types?: list<string>, collection_id?: int|null, price_min?: int|null, price_max?: int|null, in_stock?: bool, tags?: list<string>}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(
        Store $store,
        string $query,
        array $filters = [],
        int $perPage = 24,
        string $sort = 'relevance',
        string $pageName = 'page',
        ?int $page = null,
        bool $logQuery = true,
    ): LengthAwarePaginator {
        $match = $this->buildMatchExpression($store, $query, prefixLastToken: true);

        if ($match === null) {
            $results = new Paginator([], 0, max(1, $perPage), 1, ['pageName' => $pageName]);
        } else {
            $productQuery = $this->matchedProductsQuery($store, $match)
                ->with(['variants.inventoryItem', 'media']);

            $this->applyFilters($productQuery, $filters);
            $this->applySort($productQuery, $sort);

            $results = $productQuery->paginate($perPage, ['products.*'], $pageName, $page);
        }

        if ($logQuery) {
            $this->logSearch($store, $query, $filters, $results->total());
        }

        return $results;
    }

    /**
     * Prefix-matching suggestions for search-as-you-type. Returns published
     * products ordered by FTS5 relevance. Prefixes shorter than the minimum
     * length yield no results.
     *
     * @return EloquentCollection<int, Product>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): EloquentCollection
    {
        if (mb_strlen(trim($prefix)) < self::MIN_PREFIX_LENGTH) {
            return new EloquentCollection;
        }

        $match = $this->buildMatchExpression($store, $prefix, prefixLastToken: true);

        if ($match === null) {
            return new EloquentCollection;
        }

        return $this->matchedProductsQuery($store, $match)
            ->with(['variants.inventoryItem', 'media'])
            ->orderBy('fts.rank')
            ->limit($limit)
            ->get();
    }

    /**
     * Total number of published products matching the query, without
     * pagination or logging. Used for "View all X results" links.
     */
    public function countMatches(Store $store, string $query): int
    {
        $match = $this->buildMatchExpression($store, $query, prefixLastToken: true);

        if ($match === null) {
            return 0;
        }

        return $this->matchedProductsQuery($store, $match)->count();
    }

    /**
     * Distinct vendor and product type values across the matched, published
     * products. Feeds the search results page filter sidebar.
     *
     * @return array{vendors: list<string>, product_types: list<string>}
     */
    public function facetValues(Store $store, string $query): array
    {
        $match = $this->buildMatchExpression($store, $query, prefixLastToken: true);

        if ($match === null) {
            return ['vendors' => [], 'product_types' => []];
        }

        $facet = fn (string $column): array => $this->matchedProductsQuery($store, $match)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return [
            'vendors' => $facet('vendor'),
            'product_types' => $facet('product_type'),
        ];
    }

    /**
     * Upsert a product into the FTS5 index. FTS5 does not support UPDATE,
     * so the existing row (rowid = product id) is deleted first.
     */
    public function syncProduct(Product $product): void
    {
        if (! $this->indexAvailable()) {
            return;
        }

        $this->removeProduct($product->getKey());

        DB::insert(
            'INSERT INTO products_fts (rowid, store_id, product_id, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $product->getKey(),
                $product->store_id,
                $product->getKey(),
                $product->title ?? '',
                $this->plainTextDescription($product),
                $product->vendor ?? '',
                $product->product_type ?? '',
                implode(' ', $product->tags ?? []),
            ],
        );
    }

    /**
     * Remove a product from the FTS5 index.
     */
    public function removeProduct(int $productId): void
    {
        if (! $this->indexAvailable()) {
            return;
        }

        DB::delete('DELETE FROM products_fts WHERE rowid = ?', [$productId]);
    }

    /**
     * Rebuild the FTS5 index for a store from scratch.
     */
    public function reindexStore(Store $store): void
    {
        if (! $this->indexAvailable()) {
            return;
        }

        DB::delete('DELETE FROM products_fts WHERE CAST(store_id AS INTEGER) = ?', [$store->getKey()]);

        Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->each(fn (Product $product) => $this->syncProduct($product));

        Cache::put(self::REINDEXED_AT_CACHE_KEY.':'.$store->getKey(), now()->toIso8601String());
    }

    /**
     * When the store's index was last fully rebuilt, if known.
     */
    public function lastReindexedAt(Store $store): ?string
    {
        return Cache::get(self::REINDEXED_AT_CACHE_KEY.':'.$store->getKey());
    }

    /**
     * Base query for products matching an FTS5 expression: store-scoped,
     * published (active + published_at set), joined to the index so that
     * fts.rank is available for relevance ordering.
     *
     * @return Builder<Product>
     */
    protected function matchedProductsQuery(Store $store, string $match): Builder
    {
        $ftsSub = DB::table('products_fts')
            ->selectRaw('rowid AS product_id, rank')
            ->whereRaw('products_fts MATCH ?', [$match])
            ->whereRaw('CAST(store_id AS INTEGER) = ?', [$store->getKey()]);

        return Product::query()
            ->withoutGlobalScopes()
            ->joinSub($ftsSub, 'fts', 'fts.product_id', '=', 'products.id')
            ->where('products.store_id', $store->getKey())
            ->published()
            ->whereNotNull('products.published_at')
            ->select('products.*');
    }

    /**
     * @param  Builder<Product>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        $vendors = array_values(array_filter(array_merge(
            (array) ($filters['vendors'] ?? []),
            filled($filters['vendor'] ?? null) ? [$filters['vendor']] : [],
        )));

        if ($vendors !== []) {
            $query->whereIn('products.vendor', $vendors);
        }

        if (($filters['product_types'] ?? []) !== []) {
            $query->whereIn('products.product_type', $filters['product_types']);
        }

        if (filled($filters['collection_id'] ?? null)) {
            $query->whereHas('collections', function (Builder $collections) use ($filters): void {
                $collections->where('collections.id', (int) $filters['collection_id']);
            });
        }

        if (filled($filters['price_min'] ?? null)) {
            $query->whereHas('variants', fn (Builder $variants) => $variants->where('price_amount', '>=', (int) $filters['price_min']));
        }

        if (filled($filters['price_max'] ?? null)) {
            $query->whereHas('variants', fn (Builder $variants) => $variants->where('price_amount', '<=', (int) $filters['price_max']));
        }

        if (($filters['in_stock'] ?? false) === true) {
            $query->whereHas('variants.inventoryItem', function (Builder $inventory): void {
                $inventory->whereRaw('quantity_on_hand - quantity_reserved > 0');
            });
        }

        foreach ((array) ($filters['tags'] ?? []) as $tag) {
            $query->whereJsonContains('products.tags', $tag);
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    protected function applySort(Builder $query, string $sort): void
    {
        $defaultVariantPrice = ProductVariant::query()
            ->select('price_amount')
            ->whereColumn('product_id', 'products.id')
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->limit(1);

        match ($sort) {
            'price_asc' => $query->orderBy($defaultVariantPrice),
            'price_desc' => $query->orderByDesc($defaultVariantPrice),
            'newest' => $query->orderByDesc('products.created_at')->orderByDesc('products.id'),
            'best_selling' => $query->orderByDesc(
                DB::table('order_lines')->selectRaw('COALESCE(SUM(quantity), 0)')->whereColumn('order_lines.product_id', 'products.id'),
            ),
            default => $query->orderBy('fts.rank'),
        };
    }

    /**
     * Build the FTS5 MATCH expression: tokenize, drop stop words, expand
     * synonyms into OR groups, and append a prefix wildcard to the last
     * token (spec 05 section 16.3). Returns null for unsearchable input.
     */
    protected function buildMatchExpression(Store $store, string $query, bool $prefixLastToken): ?string
    {
        $tokens = $this->tokenize($query);

        if ($tokens === []) {
            return null;
        }

        $withoutStopWords = array_values(array_diff($tokens, $this->stopWords($store)));

        if ($withoutStopWords !== []) {
            $tokens = $withoutStopWords;
        }

        $groups = [];
        $lastIndex = count($tokens) - 1;

        foreach ($tokens as $index => $token) {
            $alternatives = [$this->quoteToken($token, $prefixLastToken && $index === $lastIndex)];

            foreach ($this->synonymsFor($store, $token) as $synonym) {
                $alternatives[] = $this->quoteToken($synonym, false);
            }

            $groups[] = count($alternatives) > 1
                ? '('.implode(' OR ', array_unique($alternatives)).')'
                : $alternatives[0];
        }

        return implode(' ', $groups);
    }

    /**
     * Lowercased alphanumeric tokens; all FTS5 special characters are
     * discarded by splitting on anything that is not a letter or digit.
     *
     * @return list<string>
     */
    protected function tokenize(string $text): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($tokens, fn (string $token): bool => $token !== ''));
    }

    /**
     * Quote a (possibly multi-word) term as an FTS5 phrase, optionally as a
     * prefix query. Token content is already sanitized to letters/digits.
     */
    protected function quoteToken(string $term, bool $prefix): string
    {
        $phrase = '"'.implode(' ', $this->tokenize($term)).'"';

        return $prefix ? $phrase.' *' : $phrase;
    }

    /**
     * Synonyms configured for the token via the store's synonym groups
     * (every other member of any group containing the token).
     *
     * @return list<string>
     */
    protected function synonymsFor(Store $store, string $token): array
    {
        $synonyms = [];

        foreach ($this->settings($store)?->synonymGroups() ?? [] as $group) {
            $normalized = array_map(fn (string $word): string => mb_strtolower(trim($word)), $group);
            $tokenizedMembers = array_map(fn (string $word): string => implode(' ', $this->tokenize($word)), $normalized);

            if (in_array($token, $tokenizedMembers, true)) {
                foreach ($normalized as $member) {
                    if (implode(' ', $this->tokenize($member)) !== $token) {
                        $synonyms[] = $member;
                    }
                }
            }
        }

        return array_values(array_unique($synonyms));
    }

    /**
     * @return list<string>
     */
    protected function stopWords(Store $store): array
    {
        return array_map(
            fn (string $word): string => mb_strtolower(trim($word)),
            $this->settings($store)?->stopWords() ?? [],
        );
    }

    protected function settings(Store $store): ?SearchSettings
    {
        return $this->settingsByStore[$store->getKey()] ??= SearchSettings::query()->find($store->getKey());
    }

    /**
     * Log the query to search_queries (spec 05 section 16.4) and emit a
     * "search" analytics event.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function logSearch(Store $store, string $query, array $filters, int $resultsCount): void
    {
        $query = Str::limit(trim($query), 200, '');

        if ($query === '') {
            return;
        }

        SearchQuery::query()->create([
            'store_id' => $store->getKey(),
            'query' => $query,
            'filters_json' => $filters === [] ? null : $filters,
            'results_count' => $resultsCount,
            'created_at' => now(),
        ]);

        $this->analytics->track(
            $store,
            'search',
            ['query' => $query, 'results_count' => $resultsCount],
            session()->isStarted() ? session()->getId() : null,
            auth('customer')->id(),
        );
    }

    /**
     * Strip HTML from the product description for indexing.
     */
    protected function plainTextDescription(Product $product): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($product->description_html ?? '')) ?? '');
    }

    /**
     * The FTS5 index only exists on SQLite connections.
     */
    protected function indexAvailable(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }
}
