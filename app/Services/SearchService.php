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
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $sanitized = $this->sanitizeQuery($query);

        SearchQuery::create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => $filters,
            'results_count' => 0,
        ]);

        if ($sanitized === '') {
            return Product::where('store_id', $store->id)->whereRaw('0 = 1')->paginate($perPage);
        }

        $match = $this->buildMatchQuery($sanitized);
        $productIds = DB::table('products_fts')
            ->where('store_id', $store->id)
            ->whereRaw('products_fts MATCH ?', [$match])
            ->pluck('product_id')
            ->all();

        $products = Product::where('store_id', $store->id)
            ->whereIn('id', $productIds)
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->when(isset($filters['vendor']), fn ($q) => $q->where('vendor', $filters['vendor']))
            ->when(isset($filters['collection_id']), fn ($q) => $q->whereHas('collections', fn ($c) => $c->where('collections.id', $filters['collection_id'])))
            ->when(isset($filters['price_min']), fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('price_amount', '>=', $filters['price_min'])))
            ->when(isset($filters['price_max']), fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('price_amount', '<=', $filters['price_max'])))
            ->paginate($perPage);

        SearchQuery::where('store_id', $store->id)
            ->where('query', $query)
            ->latest()
            ->first()
            ?->update(['results_count' => $products->total()]);

        return $products;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        if (trim($prefix) === '') {
            return collect();
        }

        return Product::where('store_id', $store->id)
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->where('title', 'like', $prefix.'%')
            ->limit($limit)
            ->get();
    }

    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        DB::table('products_fts')->insert([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'title' => $product->title,
            'description' => strip_tags((string) $product->description_html),
            'vendor' => (string) ($product->vendor ?? ''),
            'product_type' => (string) ($product->product_type ?? ''),
            'tags' => implode(' ', $product->tags ?? []),
        ]);
    }

    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')->where('product_id', $productId)->delete();
    }

    private function sanitizeQuery(string $query): string
    {
        $sanitized = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $query) ?? '';

        return trim(preg_replace('/\s+/', ' ', $sanitized) ?? '');
    }

    private function buildMatchQuery(string $sanitized): string
    {
        $tokens = array_values(array_filter(preg_split('/\s+/', $sanitized) ?: []));

        if ($tokens === []) {
            return '';
        }

        $last = array_pop($tokens).'*';
        $tokens[] = $last;

        return implode(' ', array_map(fn (string $token) => '"'.$token.'"', $tokens));
    }
}
