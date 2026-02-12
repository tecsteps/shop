<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchService
{
    public function reindex(?Store $store = null): void
    {
        if ($store) {
            DB::table('products_fts')->where('store_id', $store->id)->delete();
        } else {
            DB::statement('DELETE FROM products_fts');
        }

        $query = Product::query()
            ->select(['id', 'store_id', 'title', 'description_html', 'vendor', 'product_type', 'tags'])
            ->orderBy('id');

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $query->chunkById(250, function (Collection $products): void {
            $rows = $products->map(function (Product $product): array {
                $tags = is_array($product->tags) ? implode(' ', array_filter($product->tags)) : '';

                return [
                    'store_id' => $product->store_id,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'description' => trim(strip_tags((string) $product->description_html)),
                    'vendor' => (string) ($product->vendor ?? ''),
                    'product_type' => (string) ($product->product_type ?? ''),
                    'tags' => $tags,
                ];
            })->all();

            if ($rows !== []) {
                DB::table('products_fts')->insert($rows);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);
        $page = max(1, Paginator::resolveCurrentPage());
        $offset = ($page - 1) * $perPage;
        $matchExpression = $this->buildMatchExpression($query);

        if ($matchExpression === null) {
            $this->logSearch($store->id, $query, $filters, 0);

            return new Paginator(
                collect(),
                0,
                $perPage,
                $page,
                ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
            );
        }

        $total = $this->countMatchingProducts($store, $matchExpression, $filters);
        $productIds = $this->matchingProductIds($store, $matchExpression, $filters, $perPage, $offset);
        $products = $this->resolveProductsByIds($store, $productIds);

        $this->logSearch($store->id, $query, $filters, $total);

        return new Paginator(
            $products,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    public function query(Store $store, string $query, int $limit = 20): Collection
    {
        $matchExpression = $this->buildMatchExpression($query);

        if ($matchExpression === null) {
            return collect();
        }

        $productIds = $this->matchingProductIds($store, $matchExpression, [], max(1, $limit), 0);

        return $this->resolveProductsByIds($store, $productIds);
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 10): Collection
    {
        return $this->query($store, $prefix, $limit)
            ->map(static fn (Product $product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'handle' => $product->handle,
            ])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildBaseSearchQuery(Store $store, string $matchExpression, array $filters = []): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('products_fts')
            ->join('products', 'products.id', '=', 'products_fts.product_id')
            ->where('products_fts.store_id', $store->id)
            ->where('products.store_id', $store->id)
            ->where('products.status', 'active')
            ->whereNotNull('products.published_at')
            ->whereRaw('products_fts MATCH ?', [$matchExpression]);

        if (array_key_exists('vendor', $filters) && filled($filters['vendor'])) {
            $query->where('products.vendor', (string) $filters['vendor']);
        }

        if (array_key_exists('product_type', $filters) && filled($filters['product_type'])) {
            $query->where('products.product_type', (string) $filters['product_type']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function matchingProductIds(
        Store $store,
        string $matchExpression,
        array $filters,
        int $limit,
        int $offset,
    ): array {
        return $this->buildBaseSearchQuery($store, $matchExpression, $filters)
            ->selectRaw('products_fts.product_id as product_id')
            ->orderByRaw('bm25(products_fts)')
            ->offset(max(0, $offset))
            ->limit(max(1, $limit))
            ->pluck('product_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function countMatchingProducts(Store $store, string $matchExpression, array $filters): int
    {
        return (int) $this->buildBaseSearchQuery($store, $matchExpression, $filters)
            ->distinct()
            ->count('products_fts.product_id');
    }

    /**
     * @param  list<int>  $productIds
     * @return Collection<int, Product>
     */
    private function resolveProductsByIds(Store $store, array $productIds): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($productIds)
            ->map(static fn (int $id) => $products->get($id))
            ->filter()
            ->values();
    }

    private function buildMatchExpression(string $query): ?string
    {
        $normalized = Str::of($query)
            ->replaceMatches('/[^\pL\pN\s]+/u', ' ')
            ->squish()
            ->lower()
            ->trim()
            ->toString();

        if ($normalized === '') {
            return null;
        }

        $tokens = array_values(array_filter(preg_split('/\s+/', $normalized) ?: []));

        if ($tokens === []) {
            return null;
        }

        $tokens[array_key_last($tokens)] .= '*';

        return implode(' ', $tokens);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function logSearch(int $storeId, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::query()->create([
            'store_id' => $storeId,
            'query' => $query,
            'filters_json' => $filters === [] ? null : $filters,
            'results_count' => $resultsCount,
            'created_at' => now(),
        ]);
    }
}
