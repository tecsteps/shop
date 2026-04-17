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
     * Search products using FTS5.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $sanitizedQuery = $this->sanitizeQuery($query);

        if (empty($sanitizedQuery)) {
            /** @var LengthAwarePaginator<int, Product> */
            return new LengthAwarePaginator([], 0, $perPage);
        }

        /** @var \Illuminate\Database\Eloquent\Builder<Product> $productQuery */
        $productQuery = Product::query()
            ->withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', 'active')
            ->whereIn('products.id', function (\Illuminate\Database\Query\Builder $sub) use ($sanitizedQuery): void {
                $sub->select('rowid')
                    ->from('products_fts')
                    ->whereRaw('products_fts MATCH ?', [$sanitizedQuery]);
            });

        if (! empty($filters['vendor'])) {
            $productQuery->where('products.vendor', $filters['vendor']);
        }

        if (! empty($filters['price_min'])) {
            /** @var int $priceMin */
            $priceMin = $filters['price_min'];
            $productQuery->whereHas('variants', function (\Illuminate\Database\Eloquent\Builder $q) use ($priceMin): void {
                $q->where('price_amount', '>=', $priceMin);
            });
        }

        if (! empty($filters['price_max'])) {
            /** @var int $priceMax */
            $priceMax = $filters['price_max'];
            $productQuery->whereHas('variants', function (\Illuminate\Database\Eloquent\Builder $q) use ($priceMax): void {
                $q->where('price_amount', '<=', $priceMax);
            });
        }

        $paginated = $productQuery->paginate($perPage);

        /** @var SearchQuery $searchQuery */
        $searchQuery = new SearchQuery;
        $searchQuery->fill([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => ! empty($filters) ? $filters : null,
            'results_count' => $paginated->total(),
            'created_at' => now(),
        ]);
        $searchQuery->save();

        return $paginated;
    }

    /**
     * Autocomplete search using FTS5 prefix matching.
     *
     * @return Collection<int, Product>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $sanitizedPrefix = $this->sanitizeQuery($prefix);

        if (empty($sanitizedPrefix)) {
            /** @var Collection<int, Product> */
            return collect();
        }

        $ftsQuery = $sanitizedPrefix.'*';

        /** @var Collection<int, Product> */
        return Product::query()
            ->withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', 'active')
            ->whereIn('products.id', function (\Illuminate\Database\Query\Builder $sub) use ($ftsQuery): void {
                $sub->select('rowid')
                    ->from('products_fts')
                    ->whereRaw('products_fts MATCH ?', [$ftsQuery]);
            })
            ->select('products.id', 'products.title', 'products.handle')
            ->limit($limit)
            ->get();
    }

    /**
     * Sync a product into the FTS5 index.
     */
    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        DB::statement(
            'INSERT INTO products_fts(rowid, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $product->id,
                $product->title,
                strip_tags((string) $product->description_html),
                (string) $product->vendor,
                (string) $product->product_type,
                implode(' ', $product->tags ?? []),
            ]
        );
    }

    /**
     * Remove a product from the FTS5 index.
     */
    public function removeProduct(int $productId): void
    {
        try {
            DB::statement(
                "INSERT INTO products_fts(products_fts, rowid, title, description, vendor, product_type, tags) VALUES('delete', ?, '', '', '', '', '')",
                [$productId]
            );
        } catch (\Throwable) {
            // Row may not exist in the FTS index yet
        }
    }

    /**
     * Sanitize FTS5 query to prevent syntax errors.
     */
    protected function sanitizeQuery(string $query): string
    {
        $query = trim($query);
        $query = str_replace('-', ' ', $query);
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', '', $query) ?? '';

        return trim(preg_replace('/\s+/', ' ', $query) ?? '');
    }
}
