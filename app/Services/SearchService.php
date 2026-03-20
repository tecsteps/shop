<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Full-text search with store scoping, filters, and query logging.
     *
     * @param  array{vendor?: string, price_min?: int, price_max?: int, collection_id?: int, sort?: string}  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $query = trim($query);

        if ($query === '') {
            return Product::query()
                ->withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->whereRaw('1 = 0')
                ->paginate($perPage);
        }

        $ftsQuery = $this->buildFtsQuery($query);
        $matchingIds = $this->getMatchingIds($ftsQuery);

        $builder = Product::query()
            ->withoutGlobalScopes()
            ->with(['variants', 'media'])
            ->where('products.store_id', $store->id)
            ->where('products.status', 'active')
            ->whereIn('products.id', $matchingIds);

        $this->applyFilters($builder, $filters);
        $this->applySort($builder, $filters['sort'] ?? 'relevance', $matchingIds);

        $results = $builder->paginate($perPage);

        $this->logQuery($store, $query, $filters, $results->total());

        return $results;
    }

    /**
     * Prefix-based autocomplete for search-as-you-type.
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if (mb_strlen($prefix) < 2) {
            return collect();
        }

        $sanitized = str_replace('"', '', $prefix);
        $ftsQuery = '"'.$sanitized.'" *';

        $matchingIds = $this->getMatchingIds($ftsQuery);

        return Product::query()
            ->withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', 'active')
            ->whereIn('products.id', $matchingIds)
            ->select('products.id', 'products.title', 'products.handle')
            ->limit($limit)
            ->get();
    }

    /**
     * Upsert a product into the FTS5 index.
     */
    public function syncProduct(Product $product): void
    {
        $this->removeProductWithData($product);

        DB::statement(
            'INSERT INTO products_fts(rowid, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?)',
            $this->buildFtsRow($product)
        );
    }

    /**
     * Remove a product from the FTS5 index using its current data.
     */
    public function removeProduct(int $productId): void
    {
        $product = Product::withoutGlobalScopes()->find($productId);

        if ($product) {
            $this->removeProductWithData($product);
        }
    }

    /**
     * Get matching product IDs from the FTS5 index.
     *
     * @return array<int>
     */
    private function getMatchingIds(string $ftsQuery): array
    {
        $rows = DB::select(
            'SELECT rowid FROM products_fts WHERE products_fts MATCH ?',
            [$ftsQuery]
        );

        return array_map(fn ($row) => (int) $row->rowid, $rows);
    }

    /**
     * Remove a product from the FTS5 index by providing its content.
     */
    private function removeProductWithData(Product $product): void
    {
        try {
            DB::statement(
                "INSERT INTO products_fts(products_fts, rowid, title, description, vendor, product_type, tags) VALUES ('delete', ?, ?, ?, ?, ?, ?)",
                $this->buildFtsRow($product)
            );
        } catch (\Throwable) {
            // Row may not exist in FTS index yet - safe to ignore
        }
    }

    /**
     * Build the FTS row data array for a product.
     *
     * @return array<int, mixed>
     */
    private function buildFtsRow(Product $product): array
    {
        return [
            $product->id,
            $product->title ?? '',
            strip_tags($product->description_html ?? ''),
            $product->vendor ?? '',
            $product->product_type ?? '',
            is_array($product->tags) ? implode(' ', $product->tags) : ($product->tags ?? ''),
        ];
    }

    /**
     * Build an FTS5 query string from user input.
     */
    private function buildFtsQuery(string $input): string
    {
        $sanitized = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $input);
        $terms = preg_split('/\s+/', trim($sanitized), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($terms)) {
            return '""';
        }

        $escaped = array_map(function (string $term): string {
            return '"'.$term.'"';
        }, $terms);

        return implode(' ', $escaped);
    }

    /**
     * Apply filters to the search query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $builder
     * @param  array{vendor?: string, price_min?: int, price_max?: int, collection_id?: int}  $filters
     */
    private function applyFilters($builder, array $filters): void
    {
        if (! empty($filters['vendor'])) {
            $builder->where('products.vendor', $filters['vendor']);
        }

        if (! empty($filters['price_min']) || ! empty($filters['price_max'])) {
            $builder->whereHas('variants', function ($q) use ($filters) {
                if (! empty($filters['price_min'])) {
                    $q->where('price_amount', '>=', (int) $filters['price_min']);
                }
                if (! empty($filters['price_max'])) {
                    $q->where('price_amount', '<=', (int) $filters['price_max']);
                }
            });
        }

        if (! empty($filters['collection_id'])) {
            $builder->whereHas('collections', function ($q) use ($filters) {
                $q->where('collections.id', (int) $filters['collection_id']);
            });
        }
    }

    /**
     * Apply sort order to the search query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $builder
     * @param  array<int>  $matchingIds
     */
    private function applySort($builder, string $sort, array $matchingIds = []): void
    {
        match ($sort) {
            'price-asc' => $builder
                ->join('product_variants as pv_sort', 'products.id', '=', 'pv_sort.product_id')
                ->selectRaw('products.*, MIN(pv_sort.price_amount) as min_price')
                ->groupBy('products.id')
                ->orderBy('min_price', 'asc'),
            'price-desc' => $builder
                ->join('product_variants as pv_sort', 'products.id', '=', 'pv_sort.product_id')
                ->selectRaw('products.*, MAX(pv_sort.price_amount) as max_price')
                ->groupBy('products.id')
                ->orderBy('max_price', 'desc'),
            'newest' => $builder->orderBy('products.created_at', 'desc'),
            default => $builder->when(
                ! empty($matchingIds),
                fn ($q) => $q->orderByRaw(
                    'CASE products.id '.
                    collect($matchingIds)->map(fn ($id, $i) => "WHEN {$id} THEN {$i}")->implode(' ').
                    ' END'
                )
            ),
        };
    }

    /**
     * Log a search query for analytics.
     *
     * @param  array<string, mixed>  $filters
     */
    private function logQuery(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::query()
            ->withoutGlobalScopes()
            ->create([
                'store_id' => $store->id,
                'query' => $query,
                'filters_json' => ! empty($filters) ? $filters : null,
                'results_count' => $resultsCount,
                'created_at' => now(),
            ]);
    }
}
