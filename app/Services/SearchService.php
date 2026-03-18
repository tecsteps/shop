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
    public function search(Store $store, string $query, array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $sanitized = $this->sanitizeQuery($query);

        if ($sanitized === '') {
            $this->logQuery($store, $query, 0);

            return new LengthAwarePaginator([], 0, $perPage);
        }

        $ftsQuery = $this->buildFtsQuery($sanitized);

        $productIds = DB::table('products_fts')
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->where('store_id', $store->id)
            ->orderByRaw('rank')
            ->pluck('product_id');

        $productsQuery = Product::withoutGlobalScopes()
            ->whereIn('products.id', $productIds)
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'media']);

        $this->applyFilters($productsQuery, $filters);
        $this->applySort($productsQuery, $filters['sort'] ?? 'relevance', $productIds->all());

        $results = $productsQuery->paginate($perPage);

        $this->logQuery($store, $query, $results->total());

        return $results;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $sanitized = $this->sanitizeQuery($prefix);

        if ($sanitized === '') {
            return collect();
        }

        $ftsQuery = $this->buildFtsQuery($sanitized);

        $productIds = DB::table('products_fts')
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->where('store_id', $store->id)
            ->orderByRaw('rank')
            ->limit($limit)
            ->pluck('product_id');

        return Product::withoutGlobalScopes()
            ->whereIn('products.id', $productIds)
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->with(['variants' => fn ($q) => $q->where('is_default', true), 'media'])
            ->get()
            ->sortBy(fn (Product $p) => $productIds->search($p->id))
            ->values();
    }

    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        $description = strip_tags($product->description_html ?? '');
        $tags = is_array($product->tags) ? implode(' ', $product->tags) : '';

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

    public function removeProduct(int $productId): void
    {
        DB::statement('DELETE FROM products_fts WHERE product_id = ?', [$productId]);
    }

    public function reindexStore(Store $store): int
    {
        DB::statement('DELETE FROM products_fts WHERE store_id = ?', [$store->id]);

        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get();

        foreach ($products as $product) {
            $this->syncProduct($product);
        }

        return $products->count();
    }

    protected function sanitizeQuery(string $query): string
    {
        $query = trim($query);
        $query = preg_replace('/["\'\(\)\*\-\+\:\^\~]/', ' ', $query);
        $query = preg_replace('/\s+/', ' ', $query);

        return trim($query);
    }

    protected function buildFtsQuery(string $sanitized): string
    {
        $tokens = explode(' ', $sanitized);
        $tokens = array_filter($tokens, fn ($t) => $t !== '');

        if (empty($tokens)) {
            return '';
        }

        $lastIndex = count($tokens) - 1;
        $tokens[$lastIndex] = '"'.$tokens[$lastIndex].'"*';

        for ($i = 0; $i < $lastIndex; $i++) {
            $tokens[$i] = '"'.$tokens[$i].'"';
        }

        return implode(' ', $tokens);
    }

    protected function applyFilters(mixed $query, array $filters): void
    {
        if (! empty($filters['vendor'])) {
            $query->where('products.vendor', $filters['vendor']);
        }

        if (! empty($filters['vendors']) && is_array($filters['vendors'])) {
            $query->whereIn('products.vendor', $filters['vendors']);
        }

        if (! empty($filters['product_type'])) {
            $query->where('products.product_type', $filters['product_type']);
        }

        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', fn ($q) => $q->where('collections.id', $filters['collection_id']));
        }

        if (isset($filters['min_price'])) {
            $query->whereHas('variants', fn ($q) => $q->where('is_default', true)->where('price_amount', '>=', (int) $filters['min_price']));
        }

        if (isset($filters['max_price'])) {
            $query->whereHas('variants', fn ($q) => $q->where('is_default', true)->where('price_amount', '<=', (int) $filters['max_price']));
        }
    }

    protected function applySort(mixed $query, string $sort, array $productIds): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_default = 1 LIMIT 1) ASC'),
            'price_desc' => $query->orderByRaw('(SELECT price_amount FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_default = 1 LIMIT 1) DESC'),
            'newest' => $query->orderBy('products.created_at', 'desc'),
            default => $query->orderByRaw(
                $productIds
                    ? 'CASE products.id '.collect($productIds)->map(fn ($id, $i) => "WHEN {$id} THEN {$i}")->implode(' ').' ELSE 999999 END ASC'
                    : 'products.id ASC'
            ),
        };
    }

    protected function logQuery(Store $store, string $query, int $resultsCount): void
    {
        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'results_count' => $resultsCount,
            'created_at' => now(),
        ]);
    }
}
