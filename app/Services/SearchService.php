<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search products using FTS5 with store scoping and filters.
     *
     * @param  array{vendor?: string|null, collection_id?: int|null, min_price?: int|null, max_price?: int|null, sort?: string}  $filters
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
            ->orderByRaw('rank')
            ->pluck('product_id')
            ->toArray();

        if (empty($productIds)) {
            $this->logQuery($store, $query, $filters, 0);

            return new LengthAwarePaginator([], 0, $perPage);
        }

        $productsQuery = Product::withoutGlobalScopes()
            ->whereIn('products.id', $productIds)
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->with(['variants.inventoryItem', 'media']);

        $this->applyFilters($productsQuery, $filters);

        $sort = $filters['sort'] ?? 'relevance';

        if ($sort === 'price-asc') {
            $productsQuery->orderBy(
                \App\Models\ProductVariant::select('price_amount')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_default', true)
                    ->limit(1),
                'asc'
            );
        } elseif ($sort === 'price-desc') {
            $productsQuery->orderBy(
                \App\Models\ProductVariant::select('price_amount')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('is_default', true)
                    ->limit(1),
                'desc'
            );
        } elseif ($sort === 'newest') {
            $productsQuery->orderBy('products.created_at', 'desc');
        }

        $page = request()->input('page', 1);
        $total = $productsQuery->count();
        $results = $productsQuery->forPage($page, $perPage)->get();

        $this->logQuery($store, $query, $filters, $total);

        // Maintain FTS5 relevance ordering for relevance sort
        if ($sort === 'relevance') {
            $idPositions = array_flip($productIds);
            $results = $results->sort(function ($a, $b) use ($idPositions) {
                return ($idPositions[$a->id] ?? PHP_INT_MAX) <=> ($idPositions[$b->id] ?? PHP_INT_MAX);
            })->values();
        }

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * Autocomplete search for search-as-you-type.
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if (strlen($prefix) < 2) {
            return collect();
        }

        $ftsQuery = $this->buildFtsQuery($prefix);

        $productIds = DB::table('products_fts')
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->where('store_id', $store->id)
            ->orderByRaw('rank')
            ->limit($limit)
            ->pluck('product_id')
            ->toArray();

        if (empty($productIds)) {
            return collect();
        }

        $products = Product::withoutGlobalScopes()
            ->whereIn('products.id', $productIds)
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'media'])
            ->get();

        // Maintain FTS5 relevance ordering
        $idPositions = array_flip($productIds);

        return $products->sort(function ($a, $b) use ($idPositions) {
            return ($idPositions[$a->id] ?? PHP_INT_MAX) <=> ($idPositions[$b->id] ?? PHP_INT_MAX);
        })->values();
    }

    /**
     * Sync a product into the FTS5 index.
     */
    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        $description = $product->description_html
            ? strip_tags($product->description_html)
            : '';

        $tags = is_array($product->tags)
            ? implode(' ', $product->tags)
            : '';

        DB::table('products_fts')->insert([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'title' => $product->title ?? '',
            'description' => $description,
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
        DB::statement(
            'DELETE FROM products_fts WHERE product_id = ?',
            [$productId]
        );
    }

    /**
     * Build an FTS5 query string from user input.
     */
    protected function buildFtsQuery(string $input): string
    {
        // Remove FTS5 special characters
        $sanitized = preg_replace('/["\*\(\)\:\^\~\-]/', ' ', $input);
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized));

        if ($sanitized === '') {
            return '""';
        }

        $tokens = explode(' ', $sanitized);
        $tokens = array_filter($tokens, fn ($t) => $t !== '');

        if (empty($tokens)) {
            return '""';
        }

        // Add prefix matching to the last token for autocomplete behavior
        $lastIndex = count($tokens) - 1;
        $tokens[$lastIndex] = $tokens[$lastIndex].'*';

        return implode(' ', $tokens);
    }

    /**
     * Apply search filters to the products query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @param  array{vendor?: string|null, collection_id?: int|null, min_price?: int|null, max_price?: int|null, sort?: string}  $filters
     */
    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['vendor'])) {
            $query->where('products.vendor', $filters['vendor']);
        }

        if (! empty($filters['vendors']) && is_array($filters['vendors'])) {
            $query->whereIn('products.vendor', $filters['vendors']);
        }

        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', function ($q) use ($filters): void {
                $q->where('collections.id', $filters['collection_id']);
            });
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $query->whereHas('variants', function ($q) use ($filters): void {
                $q->where('price_amount', '>=', (int) $filters['min_price']);
            });
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $query->whereHas('variants', function ($q) use ($filters): void {
                $q->where('price_amount', '<=', (int) $filters['max_price']);
            });
        }

        if (! empty($filters['product_types']) && is_array($filters['product_types'])) {
            $query->whereIn('products.product_type', $filters['product_types']);
        }
    }

    /**
     * Log the search query for analytics.
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
}
