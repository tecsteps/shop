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
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $ftsQuery = $this->sanitizeQuery($query);

        $productIds = collect();

        if ($ftsQuery !== '') {
            $productIds = DB::table('products_fts')
                ->whereRaw('products_fts MATCH ?', [$ftsQuery])
                ->where('store_id', $store->id)
                ->pluck('product_id');
        }

        $builder = Product::withoutGlobalScopes()
            ->where('store_id', $store->id);

        if ($ftsQuery !== '') {
            if ($productIds->isEmpty()) {
                $builder->whereRaw('0 = 1');
            } else {
                $builder->whereIn('id', $productIds);
            }
        }

        if (isset($filters['vendor'])) {
            $builder->where('vendor', $filters['vendor']);
        }

        if (isset($filters['product_type'])) {
            $builder->where('product_type', $filters['product_type']);
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $builder->whereHas('variants', function ($q) use ($filters): void {
                if (isset($filters['min_price'])) {
                    $q->where('price_amount', '>=', $filters['min_price']);
                }
                if (isset($filters['max_price'])) {
                    $q->where('price_amount', '<=', $filters['max_price']);
                }
            });
        }

        if (isset($filters['collection_id'])) {
            $builder->whereHas('collections', function ($q) use ($filters): void {
                $q->where('collections.id', $filters['collection_id']);
            });
        }

        $results = $builder->paginate($perPage);

        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'results_count' => $results->total(),
        ]);

        return $results;
    }

    /**
     * @return Collection<int, string>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        if (mb_strlen(trim($prefix)) < 2) {
            /** @var Collection<int, string> */
            return collect();
        }

        $sanitized = $this->sanitizeForPrefix($prefix);

        if ($sanitized === '') {
            /** @var Collection<int, string> */
            return collect();
        }

        $results = DB::table('products_fts')
            ->selectRaw('product_id, title')
            ->whereRaw('products_fts MATCH ?', ['"'.$sanitized.'" *'])
            ->where('store_id', $store->id)
            ->limit($limit)
            ->get();

        /** @var Collection<int, string> */
        return $results->pluck('title')->unique()->values();
    }

    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        $tags = is_array($product->tags) ? implode(' ', $product->tags) : ($product->tags ?? '');

        DB::table('products_fts')->insert([
            'product_id' => $product->id,
            'store_id' => $product->store_id,
            'title' => $product->title ?? '',
            'description' => strip_tags($product->description_html ?? ''),
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

    private function sanitizeQuery(string $query): string
    {
        $query = trim($query);

        $query = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $query);

        return trim($query ?? '');
    }

    private function sanitizeForPrefix(string $prefix): string
    {
        $prefix = trim($prefix);

        $prefix = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $prefix);

        return trim($prefix ?? '');
    }
}
