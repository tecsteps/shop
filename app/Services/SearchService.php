<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * @param  array{vendor?: string, priceMin?: int|null, priceMax?: int|null, collection?: int|null}  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 12, string $sort = 'relevance'): LengthAwarePaginator
    {
        $query = trim($query);

        if ($query === '') {
            return Product::query()->where('id', 0)->paginate($perPage);
        }

        $ftsIds = $this->ftsSearch($store->id, $query);

        $productQuery = Product::withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->whereHas('variants', fn ($q) => $q->where('status', VariantStatus::Active));

        if ($ftsIds->isNotEmpty()) {
            $productQuery->whereIn('products.id', $ftsIds);
        } else {
            $productQuery->where('products.title', 'LIKE', '%'.$query.'%');
        }

        if (! empty($filters['vendor'])) {
            $productQuery->where('products.vendor', $filters['vendor']);
        }

        if (! empty($filters['priceMin'])) {
            $productQuery->whereHas('variants', fn ($q) => $q->where('price_amount', '>=', (int) $filters['priceMin'] * 100));
        }

        if (! empty($filters['priceMax'])) {
            $productQuery->whereHas('variants', fn ($q) => $q->where('price_amount', '<=', (int) $filters['priceMax'] * 100));
        }

        if (! empty($filters['collection'])) {
            $productQuery->whereHas('collections', fn ($q) => $q->where('collections.id', $filters['collection']));
        }

        $productQuery = match ($sort) {
            'price-asc' => $productQuery->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.status = ?) ASC', [VariantStatus::Active->value]),
            'price-desc' => $productQuery->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.status = ?) DESC', [VariantStatus::Active->value]),
            'newest' => $productQuery->orderBy('products.created_at', 'desc'),
            default => $productQuery->orderBy('products.title'),
        };

        $productQuery->with([
            'variants' => fn ($q) => $q->where('status', VariantStatus::Active),
            'variants.inventoryItem',
            'media',
        ]);

        $results = $productQuery->paginate($perPage);

        $this->logQuery($store, $query, $results->total(), $filters);

        return $results;
    }

    /**
     * @return Collection<int, array{id: int, title: string, handle: string}>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if (mb_strlen($prefix) < 2) {
            return collect();
        }

        $ftsIds = $this->ftsSearch($store->id, $prefix.'*');

        $query = Product::withoutGlobalScopes()
            ->where('products.store_id', $store->id)
            ->where('products.status', ProductStatus::Active)
            ->whereNotNull('products.published_at')
            ->select('id', 'title', 'handle');

        if ($ftsIds->isNotEmpty()) {
            $query->whereIn('id', $ftsIds);
        } else {
            $query->where('title', 'LIKE', $prefix.'%');
        }

        return $query->limit($limit)->get()->map(fn (Product $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'handle' => $p->handle,
        ]);
    }

    public function syncProduct(Product $product): void
    {
        $this->removeProduct($product->id);

        if ($product->status !== ProductStatus::Active) {
            return;
        }

        $tags = is_array($product->tags) ? implode(' ', $product->tags) : ($product->tags ?? '');

        DB::table('products_fts')->insert([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'title' => $product->title ?? '',
            'description' => strip_tags($product->description_html ?? ''),
            'vendor' => $product->vendor ?? '',
            'product_type' => $product->product_type ?? '',
            'tags' => $tags,
        ]);
    }

    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')->where('product_id', $productId)->delete();
    }

    public function reindexAll(Store $store): int
    {
        DB::table('products_fts')->where('store_id', $store->id)->delete();

        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->get();

        foreach ($products as $product) {
            $this->syncProduct($product);
        }

        return $products->count();
    }

    /**
     * @return Collection<int, int>
     */
    private function ftsSearch(int $storeId, string $query): Collection
    {
        $ftsQuery = $this->sanitizeFtsQuery($query);

        if ($ftsQuery === '') {
            return collect();
        }

        try {
            $results = DB::select(
                'SELECT product_id FROM products_fts WHERE store_id = ? AND products_fts MATCH ?',
                [$storeId, $ftsQuery]
            );

            return collect($results)->pluck('product_id');
        } catch (\Exception) {
            return collect();
        }
    }

    private function sanitizeFtsQuery(string $query): string
    {
        $query = preg_replace('/[^\p{L}\p{N}\s*]/u', '', $query);

        return trim($query ?? '');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function logQuery(Store $store, string $query, int $resultsCount, array $filters = []): void
    {
        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => ! empty($filters) ? $filters : null,
            'results_count' => $resultsCount,
        ]);
    }
}
