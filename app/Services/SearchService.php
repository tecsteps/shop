<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Full-text product search backed by SQLite FTS5 (`products_fts`).
 *
 * The index is denormalized from the products table and kept in sync via
 * {@see \App\Observers\ProductObserver}. Reads are always scoped to a store and
 * limited to active products. `search()` additionally applies storefront facets
 * (collection, price range, stock, tags, vendor) and logs the query for
 * analytics; `autocomplete()` is the lightweight prefix variant used by the
 * search-as-you-type modal.
 *
 * All amounts are integers in minor units (cents).
 */
class SearchService
{
    /**
     * Minimum prefix length before autocomplete returns suggestions.
     */
    public const AUTOCOMPLETE_MIN_LENGTH = 2;

    /**
     * Run a full-text product search within a store, returning a paginator of
     * hydrated {@see Product} models (eager-loaded for storefront cards).
     *
     * @param  array{collection_id?: int, price_min?: int, price_max?: int, in_stock?: bool, tags?: list<string>, vendor?: string, sort?: string}  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 24, int $page = 1): LengthAwarePaginator
    {
        $ids = $this->matchingProductIds($store, $query);

        $builder = $this->baseQuery($store, $ids)
            ->with(['variants.inventoryItem', 'media'])
            ->select('products.*');

        $this->applyFilters($builder, $filters);
        $this->applySort($builder, $filters['sort'] ?? 'relevance', $ids);

        $paginator = $builder->paginate(perPage: $perPage, page: $page);

        $this->logQuery($store, $query, $filters, $paginator->total());

        return $paginator;
    }

    /**
     * Return up to `$limit` product suggestions whose indexed text matches the
     * given prefix. Returns an empty collection for prefixes below the minimum
     * length.
     *
     * @return Collection<int, Product>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if (Str::length($prefix) < self::AUTOCOMPLETE_MIN_LENGTH) {
            return new Collection;
        }

        $ids = $this->matchingProductIds($store, $prefix, prefix: true);

        if ($ids === []) {
            return new Collection;
        }

        return $this->baseQuery($store, $ids)
            ->with(['variants', 'media'])
            ->orderByRaw($this->orderByIdList($ids))
            ->limit($limit)
            ->get();
    }

    /**
     * Upsert a product into the FTS index. Drafts/archived products are removed
     * so only sellable products appear in results.
     */
    public function syncProduct(Product $product): void
    {
        if ($product->status !== ProductStatus::Active) {
            $this->removeProduct($product->id);

            return;
        }

        $this->removeProduct($product->id);

        DB::table('products_fts')->insert([
            'rowid' => $product->id,
            'title' => (string) $product->title,
            'description' => $this->plainText($product->description_html),
            'vendor' => (string) $product->vendor,
            'product_type' => (string) $product->product_type,
            'tags' => $this->tagsText($product->tags),
            'store_id' => $product->store_id,
        ]);
    }

    /**
     * Remove a product from the FTS index by id (no-op if absent).
     */
    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')->where('rowid', $productId)->delete();
    }

    /**
     * Rebuild the entire FTS index for a store from its active products.
     */
    public function reindexStore(Store $store): int
    {
        DB::table('products_fts')->where('store_id', $store->id)->delete();

        $count = 0;

        Product::query()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active->value)
            ->chunkById(200, function (Collection $products) use (&$count): void {
                foreach ($products as $product) {
                    $this->syncProduct($product);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Resolve the product ids matching a query within a store, ordered by FTS5
     * relevance (rank). Returns an empty list for blank queries.
     *
     * @return list<int>
     */
    private function matchingProductIds(Store $store, string $query, bool $prefix = false): array
    {
        $match = $this->buildMatchExpression($query, $prefix);

        if ($match === null) {
            return [];
        }

        return DB::table('products_fts')
            ->where('store_id', $store->id)
            ->whereRaw('products_fts MATCH ?', [$match])
            ->orderByRaw('rank')
            ->pluck('rowid')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Build a safe FTS5 MATCH expression from free-text input. Each token is
     * quoted to neutralize FTS operators; in prefix mode a trailing `*` enables
     * search-as-you-type. Returns null when no usable token remains.
     */
    private function buildMatchExpression(string $query, bool $prefix): ?string
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(trim($query)), flags: PREG_SPLIT_NO_EMPTY);

        if ($tokens === false || $tokens === []) {
            return null;
        }

        $terms = array_map(function (string $token) use ($prefix): string {
            $escaped = str_replace('"', '', $token);

            return $prefix ? '"'.$escaped.'"*' : '"'.$escaped.'"';
        }, $tokens);

        return implode(' ', $terms);
    }

    /**
     * Base store-scoped, active-product query constrained to the given ids. When
     * the id list is empty an impossible constraint yields no results.
     *
     * @param  list<int>  $ids
     * @return Builder<Product>
     */
    private function baseQuery(Store $store, array $ids): Builder
    {
        $query = Product::query()
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active->value);

        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('products.id', $ids);
    }

    /**
     * Apply storefront facet filters to the product query.
     *
     * @param  Builder<Product>  $builder
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $builder, array $filters): void
    {
        if (! empty($filters['collection_id'])) {
            $builder->whereHas('collections', function (Builder $query) use ($filters): void {
                $query->where('collections.id', $filters['collection_id']);
            });
        }

        if (! empty($filters['vendor'])) {
            $builder->where('products.vendor', $filters['vendor']);
        }

        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $builder->whereHas('variants', function (Builder $query) use ($filters): void {
                if (isset($filters['price_min'])) {
                    $query->where('price_amount', '>=', (int) $filters['price_min']);
                }

                if (isset($filters['price_max'])) {
                    $query->where('price_amount', '<=', (int) $filters['price_max']);
                }
            });
        }

        if (! empty($filters['in_stock'])) {
            $builder->whereHas('variants.inventoryItem', function (Builder $query): void {
                $query->whereColumn('quantity_on_hand', '>', 'quantity_reserved');
            });
        }

        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $builder->whereJsonContains('products.tags', $tag);
            }
        }
    }

    /**
     * Apply the requested sort. Relevance preserves FTS5 rank ordering; the
     * remaining sorts join the primary variant price or fall back to recency.
     *
     * @param  Builder<Product>  $builder
     * @param  list<int>  $ids
     */
    private function applySort(Builder $builder, string $sort, array $ids): void
    {
        switch ($sort) {
            case 'price_asc':
            case 'price_desc':
                $direction = $sort === 'price_asc' ? 'asc' : 'desc';
                $builder->orderBy(
                    DB::table('product_variants')
                        ->selectRaw('MIN(price_amount)')
                        ->whereColumn('product_variants.product_id', 'products.id'),
                    $direction,
                );
                break;
            case 'newest':
                $builder->orderByDesc('products.published_at')->orderByDesc('products.id');
                break;
            case 'best_selling':
                // No sales aggregate available yet; fall back to recency.
                $builder->orderByDesc('products.published_at')->orderByDesc('products.id');
                break;
            case 'relevance':
            default:
                $builder->orderByRaw($this->orderByIdList($ids));
                break;
        }
    }

    /**
     * Build a CASE expression that preserves the FTS5 relevance order of ids.
     *
     * @param  list<int>  $ids
     */
    private function orderByIdList(array $ids): string
    {
        if ($ids === []) {
            return '1';
        }

        $cases = [];

        foreach (array_values($ids) as $position => $id) {
            $cases[] = 'WHEN '.(int) $id.' THEN '.$position;
        }

        return 'CASE products.id '.implode(' ', $cases).' ELSE '.count($ids).' END';
    }

    /**
     * Log a search query for analytics and popular-search suggestions.
     *
     * @param  array<string, mixed>  $filters
     */
    private function logQuery(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => $filters === [] ? null : $filters,
            'results_count' => $resultsCount,
        ]);
    }

    /**
     * Reduce HTML description to indexable plain text.
     */
    private function plainText(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    }

    /**
     * Flatten a product's tags into a space-separated string for indexing.
     *
     * @param  list<string>|null  $tags
     */
    private function tagsText(?array $tags): string
    {
        return $tags === null ? '' : implode(' ', $tags);
    }
}
