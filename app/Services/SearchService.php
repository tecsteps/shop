<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search products using FTS5 with store scoping.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = trim($query);

        if ($query === '') {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $ftsQuery = $this->buildFtsQuery($query);

        $productIds = DB::table('products_fts')
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->where('store_id', $store->id)
            ->pluck('product_id');

        $productsQuery = Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->whereIn('id', $productIds);

        if (! empty($filters['vendor'])) {
            $productsQuery->where('vendor', $filters['vendor']);
        }

        if (! empty($filters['product_type'])) {
            $productsQuery->where('product_type', $filters['product_type']);
        }

        if (isset($filters['min_price'])) {
            $productsQuery->whereHas('variants', function ($q) use ($filters) {
                $q->where('price_amount', '>=', (int) $filters['min_price'] * 100);
            });
        }

        if (isset($filters['max_price'])) {
            $productsQuery->whereHas('variants', function ($q) use ($filters) {
                $q->where('price_amount', '<=', (int) $filters['max_price'] * 100);
            });
        }

        $sort = $filters['sort'] ?? 'relevance';
        $productsQuery = match ($sort) {
            'price_asc' => $productsQuery->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) ASC'),
            'price_desc' => $productsQuery->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) DESC'),
            'newest' => $productsQuery->orderBy('created_at', 'desc'),
            default => $productsQuery->orderByRaw('FIELD(id, '.($productIds->isEmpty() ? '0' : $productIds->implode(',')).')'),
        };

        $results = $productsQuery->paginate($perPage);

        $this->logQuery($store, $query, $filters, $results->total());

        return $results;
    }

    /**
     * Autocomplete search for search-as-you-type.
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return collect();
        }

        $ftsQuery = $this->buildPrefixQuery($prefix);

        $productIds = DB::table('products_fts')
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->where('store_id', $store->id)
            ->limit($limit)
            ->pluck('product_id');

        return Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->whereIn('id', $productIds)
            ->with('media')
            ->limit($limit)
            ->get();
    }

    /**
     * Sync a product into the FTS5 index.
     */
    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        $tags = $product->tags;
        $tagsString = is_array($tags) ? implode(' ', $tags) : ($tags ?? '');

        DB::table('products_fts')->insert([
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title ?? '',
            'description' => strip_tags($product->description_html ?? ''),
            'vendor' => $product->vendor ?? '',
            'product_type' => $product->product_type ?? '',
            'tags' => $tagsString,
        ]);
    }

    /**
     * Remove a product from the FTS5 index.
     */
    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')
            ->where('product_id', $productId)
            ->delete();
    }

    /**
     * Rebuild the entire FTS5 index for a store.
     */
    public function rebuildIndex(Store $store): void
    {
        DB::table('products_fts')
            ->where('store_id', $store->id)
            ->delete();

        Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->chunk(100, function ($products) {
                foreach ($products as $product) {
                    $this->syncProduct($product);
                }
            });
    }

    /**
     * Build an FTS5 match query from user input.
     */
    protected function buildFtsQuery(string $query): string
    {
        $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($terms)) {
            return '""';
        }

        $escaped = array_map(function (string $term): string {
            return '"'.str_replace('"', '""', $term).'"';
        }, $terms);

        return implode(' ', $escaped);
    }

    /**
     * Build an FTS5 prefix match query for autocomplete.
     */
    protected function buildPrefixQuery(string $prefix): string
    {
        $terms = preg_split('/\s+/', $prefix, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($terms)) {
            return '""';
        }

        $escaped = [];
        foreach ($terms as $i => $term) {
            $safe = str_replace('"', '""', $term);
            if ($i === count($terms) - 1) {
                $escaped[] = '"'.$safe.'" *';
            } else {
                $escaped[] = '"'.$safe.'"';
            }
        }

        return implode(' ', $escaped);
    }

    /**
     * Log a search query for analytics.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function logQuery(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::query()->withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => ! empty($filters) ? $filters : null,
            'results_count' => $resultsCount,
            'created_at' => now(),
        ]);
    }
}
