<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchService
{
    /**
     * Perform a full-text search scoped to a store, with optional filters.
     *
     * @param  array{vendor?: string, price_min?: int, price_max?: int, collection_id?: int}  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = trim($query);

        if ($query === '') {
            return Product::query()->whereRaw('1 = 0')->paginate($perPage);
        }

        $ftsExists = $this->ftsTableExists();

        $productQuery = Product::withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', 'active');

        if ($ftsExists) {
            $ftsQuery = $this->buildFtsQuery($query);

            $productQuery->join('products_fts', function ($join) use ($store, $ftsQuery) {
                $join->on('products.id', '=', DB::raw('CAST(products_fts.product_id AS INTEGER)'))
                    ->whereRaw('products_fts.store_id = ?', [(string) $store->id])
                    ->whereRaw('products_fts MATCH ?', [$ftsQuery]);
            })
                ->selectRaw('products.*, rank AS fts_rank')
                ->orderBy('fts_rank');
        } else {
            $likePattern = '%'.$query.'%';
            $productQuery->where(function ($q) use ($likePattern) {
                $q->where('products.title', 'like', $likePattern)
                    ->orWhere('products.description_html', 'like', $likePattern)
                    ->orWhere('products.vendor', 'like', $likePattern)
                    ->orWhere('products.product_type', 'like', $likePattern);
            });
        }

        $this->applyFilters($productQuery, $filters);

        $productQuery->with(['variants.inventoryItem', 'media']);

        $results = $productQuery->paginate($perPage);

        $this->logQuery($store, $query, $filters, $results->total());

        return $results;
    }

    /**
     * Return autocomplete suggestions for a prefix query.
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if (mb_strlen($prefix) < 2) {
            return collect();
        }

        $ftsExists = $this->ftsTableExists();

        if ($ftsExists) {
            $ftsPrefix = $this->escapeFtsToken($prefix).'*';

            return Product::withoutGlobalScopes()
                ->where('products.store_id', $store->id)
                ->where('products.status', 'active')
                ->join('products_fts', function ($join) use ($store, $ftsPrefix) {
                    $join->on('products.id', '=', DB::raw('CAST(products_fts.product_id AS INTEGER)'))
                        ->whereRaw('products_fts.store_id = ?', [(string) $store->id])
                        ->whereRaw('products_fts MATCH ?', [$ftsPrefix]);
                })
                ->select('products.id', 'products.title', 'products.handle')
                ->orderByRaw('rank')
                ->limit($limit)
                ->get();
        }

        return Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->where('title', 'like', $prefix.'%')
            ->select('id', 'title', 'handle')
            ->limit($limit)
            ->get();
    }

    /**
     * Upsert a product into the FTS5 index.
     */
    public function syncProduct(Product $product): void
    {
        if (! $this->ftsTableExists()) {
            return;
        }

        $this->removeProduct($product->id);

        $tags = is_array($product->tags) ? implode(' ', $product->tags) : ($product->tags ?? '');

        DB::table('products_fts')->insert([
            'product_id' => (string) $product->id,
            'store_id' => (string) $product->store_id,
            'title' => $product->title ?? '',
            'description' => strip_tags($product->description_html ?? ''),
            'vendor' => $product->vendor ?? '',
            'product_type' => $product->product_type ?? '',
            'tags' => $tags,
        ]);
    }

    /**
     * Remove a product from the FTS5 index.
     */
    public function removeProduct(int $productId): void
    {
        if (! $this->ftsTableExists()) {
            return;
        }

        DB::statement(
            'DELETE FROM products_fts WHERE product_id = ?',
            [(string) $productId]
        );
    }

    /**
     * Build an FTS5 MATCH query from user input, escaping special characters.
     */
    protected function buildFtsQuery(string $query): string
    {
        $tokens = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);

        $escaped = array_map(fn (string $token) => $this->escapeFtsToken($token), $tokens);

        return implode(' ', $escaped);
    }

    /**
     * Escape a single token for safe use in FTS5 MATCH expressions.
     */
    protected function escapeFtsToken(string $token): string
    {
        $token = str_replace('"', '', $token);

        return '"'.$token.'"';
    }

    /**
     * Apply optional filters to the product query.
     *
     * @param  array{vendor?: string, price_min?: int, price_max?: int, collection_id?: int}  $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['vendor'])) {
            $query->where('products.vendor', $filters['vendor']);
        }

        if (! empty($filters['price_min']) || ! empty($filters['price_max'])) {
            $query->whereHas('variants', function ($vq) use ($filters) {
                if (! empty($filters['price_min'])) {
                    $vq->where('price_amount', '>=', (int) $filters['price_min']);
                }
                if (! empty($filters['price_max'])) {
                    $vq->where('price_amount', '<=', (int) $filters['price_max']);
                }
            });
        }

        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', function ($cq) use ($filters) {
                $cq->where('collections.id', $filters['collection_id']);
            });
        }
    }

    /**
     * Log a search query for analytics.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function logQuery(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => ! empty($filters) ? $filters : null,
            'results_count' => $resultsCount,
        ]);
    }

    /**
     * Check whether the FTS5 virtual table exists.
     */
    protected function ftsTableExists(): bool
    {
        return Schema::hasTable('products_fts');
    }
}
