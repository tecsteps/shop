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
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $ftsQuery = $this->sanitizeQuery($query);

        $matchingProductIds = collect();
        if ($ftsQuery !== '') {
            $matchingProductIds = collect(
                DB::select('SELECT rowid FROM products_fts WHERE products_fts MATCH ?', [$ftsQuery])
            )->pluck('rowid');
        }

        $productQuery = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active');

        if ($ftsQuery !== '' && $matchingProductIds->isEmpty()) {
            $productQuery->whereRaw('0 = 1');
        } elseif ($ftsQuery !== '') {
            $productQuery->whereIn('id', $matchingProductIds);
        }

        if (! empty($filters['vendor'])) {
            $productQuery->where('vendor', $filters['vendor']);
        }

        if (! empty($filters['product_type'])) {
            $productQuery->where('product_type', $filters['product_type']);
        }

        if (! empty($filters['collection_id'])) {
            $productQuery->whereHas('collections', function ($q) use ($filters): void {
                $q->where('collections.id', $filters['collection_id']);
            });
        }

        if (! empty($filters['price_min']) || ! empty($filters['price_max'])) {
            $productQuery->whereHas('variants', function ($q) use ($filters): void {
                if (! empty($filters['price_min'])) {
                    $q->where('price', '>=', $filters['price_min']);
                }
                if (! empty($filters['price_max'])) {
                    $q->where('price', '<=', $filters['price_max']);
                }
            });
        }

        $results = $productQuery->paginate($perPage);

        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'results_count' => $results->total(),
            'created_at' => now(),
        ]);

        return $results;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $sanitized = $this->sanitizeQuery($prefix);
        if ($sanitized === '') {
            return collect();
        }

        $prefixMatch = $sanitized.'*';

        $rows = DB::select(
            'SELECT rowid FROM products_fts WHERE products_fts MATCH ? LIMIT ?',
            [$prefixMatch, $limit * 2]
        );

        $productIds = collect($rows)->pluck('rowid');

        return Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->whereIn('id', $productIds)
            ->limit($limit)
            ->get(['id', 'title', 'handle']);
    }

    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        $tags = is_array($product->tags) ? implode(' ', $product->tags) : ($product->tags ?? '');

        DB::statement(
            'INSERT INTO products_fts(rowid, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $product->id,
                $product->title ?? '',
                strip_tags($product->description_html ?? ''),
                $product->vendor ?? '',
                $product->product_type ?? '',
                $tags,
            ]
        );
    }

    public function removeProduct(int $productId): void
    {
        DB::statement('DELETE FROM products_fts WHERE rowid = ?', [$productId]);
    }

    protected function sanitizeQuery(string $query): string
    {
        $query = trim($query);
        $query = preg_replace('/[^\w\s]/u', '', $query);

        return trim($query);
    }
}
