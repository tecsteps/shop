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
    public function search(Store $store, string $query, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = trim($query);

        if ($query === '') {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $ftsQuery = $this->buildFtsQuery($query);

        $productIds = DB::table('products_fts')
            ->selectRaw('product_id, rank')
            ->where('store_id', $store->id)
            ->whereRaw('products_fts MATCH ?', [$ftsQuery])
            ->orderBy('rank')
            ->pluck('product_id')
            ->all();

        $productsQuery = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->whereIn('id', $productIds);

        if (! empty($filters['vendor'])) {
            $productsQuery->where('vendor', $filters['vendor']);
        }

        if (! empty($filters['product_type'])) {
            $productsQuery->where('product_type', $filters['product_type']);
        }

        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $productsQuery->whereHas('variants', function ($q) use ($filters) {
                if (isset($filters['price_min'])) {
                    $q->where('price_amount', '>=', $filters['price_min']);
                }
                if (isset($filters['price_max'])) {
                    $q->where('price_amount', '<=', $filters['price_max']);
                }
            });
        }

        $sortField = $filters['sort'] ?? 'relevance';
        match ($sortField) {
            'price_asc' => $productsQuery->orderByRaw(
                '(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) ASC'
            ),
            'price_desc' => $productsQuery->orderByRaw(
                '(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) DESC'
            ),
            'newest' => $productsQuery->orderByDesc('created_at'),
            default => $this->orderByRelevance($productsQuery, $productIds),
        };

        $result = $productsQuery->paginate($perPage);

        SearchQuery::create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => $filters ?: null,
            'results_count' => $result->total(),
            'created_at' => now(),
        ]);

        return $result;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return collect();
        }

        $ftsPrefix = '"'.str_replace('"', '""', $prefix).'" *';

        return DB::table('products_fts')
            ->join('products', 'products.id', '=', 'products_fts.product_id')
            ->where('products_fts.store_id', $store->id)
            ->where('products.status', 'active')
            ->whereRaw('products_fts MATCH ?', [$ftsPrefix])
            ->select('products.id', 'products.title', 'products.handle')
            ->limit($limit)
            ->get();
    }

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
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title,
            'description' => $description,
            'vendor' => $product->vendor ?? '',
            'product_type' => $product->product_type ?? '',
            'tags' => $tags,
        ]);
    }

    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')
            ->where('product_id', $productId)
            ->delete();
    }

    private function buildFtsQuery(string $query): string
    {
        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        $escaped = array_map(function ($word) {
            return '"'.str_replace('"', '""', $word).'"';
        }, $words);

        return implode(' ', $escaped);
    }

    /**
     * Order results by FTS relevance using a CASE expression (SQLite-compatible).
     */
    private function orderByRelevance(\Illuminate\Database\Eloquent\Builder $query, array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $cases = [];
        foreach ($productIds as $index => $id) {
            $cases[] = "WHEN {$id} THEN {$index}";
        }

        $query->orderByRaw('CASE id '.implode(' ', $cases).' ELSE '.count($productIds).' END');
    }
}
