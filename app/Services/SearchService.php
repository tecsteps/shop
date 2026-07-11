<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Models\SearchQuery;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchService
{
    /** @param array<string, mixed> $filters */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 24): LengthAwarePaginator
    {
        $perPage = max(1, min(50, $perPage));
        $productIds = $this->matchingProductIds($store, $query);

        if ($productIds->isEmpty()) {
            $paginator = new LengthAwarePaginator([], 0, $perPage, LengthAwarePaginator::resolveCurrentPage());
            $this->logQuery($store, $query, $filters, 0);

            return $paginator;
        }

        $products = Product::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->whereIn('id', $productIds)
            ->when(filled($filters['vendor'] ?? null), fn (Builder $builder): Builder => $builder->where('vendor', $filters['vendor']))
            ->when(filled($filters['collection_id'] ?? null), fn (Builder $builder): Builder => $builder->whereHas(
                'collections',
                fn (Builder $collectionQuery): Builder => $collectionQuery->whereKey((int) $filters['collection_id']),
            ))
            ->when(is_array($filters['tags'] ?? null), function (Builder $builder) use ($filters): void {
                foreach ($filters['tags'] as $tag) {
                    $builder->whereJsonContains('tags', $tag);
                }
            })
            ->when(isset($filters['price_min']) || isset($filters['price_max']), function (Builder $builder) use ($filters): void {
                $builder->whereHas('variants', function (Builder $variantQuery) use ($filters): void {
                    $variantQuery
                        ->when(isset($filters['price_min']), fn (Builder $query): Builder => $query->where('price_amount', '>=', (int) $filters['price_min']))
                        ->when(isset($filters['price_max']), fn (Builder $query): Builder => $query->where('price_amount', '<=', (int) $filters['price_max']));
                });
            })
            ->when(($filters['in_stock'] ?? false) === true, fn (Builder $builder): Builder => $builder->whereHas(
                'variants.inventoryItem',
                fn (Builder $inventoryQuery): Builder => $inventoryQuery->whereColumn('quantity_on_hand', '>', 'quantity_reserved'),
            ));

        $this->applySort($products, $productIds, (string) ($filters['sort'] ?? 'relevance'));

        $paginator = $products->paginate($perPage);
        $this->logQuery($store, $query, $filters, $paginator->total());

        return $paginator;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $limit = max(1, min(10, $limit));
        $productIds = $this->matchingProductIds($store, $prefix, $limit * 2);

        if ($productIds->isEmpty()) {
            return collect();
        }

        $products = Product::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return $productIds
            ->map(fn (int $id): ?Product => $products->get($id))
            ->filter()
            ->take($limit)
            ->values();
    }

    public function syncProduct(Product $product): void
    {
        DB::table('products_fts')->where('product_id', $product->id)->delete();

        DB::table('products_fts')->insert([
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'title' => $product->title,
            'description' => strip_tags((string) $product->description_html),
            'vendor' => $product->vendor ?? '',
            'product_type' => $product->product_type ?? '',
            'tags' => implode(' ', $product->tags ?? []),
        ]);
    }

    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')->where('product_id', $productId)->delete();
    }

    private function matchingProductIds(Store $store, string $query, ?int $limit = null): Collection
    {
        $matchExpression = $this->matchExpression($query);

        if ($matchExpression === '') {
            return collect();
        }

        return DB::table('products_fts')
            ->where('store_id', $store->id)
            ->whereRaw('products_fts MATCH ?', [$matchExpression])
            ->orderByRaw('bm25(products_fts)')
            ->when($limit !== null, fn ($builder) => $builder->limit($limit))
            ->pluck('product_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    private function matchExpression(string $query): string
    {
        preg_match_all('/[\pL\pN]+/u', Str::squish($query), $matches);
        $tokens = $matches[0] ?? [];

        if ($tokens === []) {
            return '';
        }

        $lastIndex = array_key_last($tokens);

        return collect($tokens)
            ->map(fn (string $token, int $index): string => '"'.$token.'"'.($index === $lastIndex ? '*' : ''))
            ->implode(' AND ');
    }

    private function applySort(Builder $builder, Collection $productIds, string $sort): void
    {
        if ($sort === 'newest') {
            $builder->latest('published_at');

            return;
        }

        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $builder->withMin('variants', 'price_amount')
                ->orderBy('variants_min_price_amount', $sort === 'price_asc' ? 'asc' : 'desc');

            return;
        }

        $orderCases = $productIds
            ->values()
            ->map(fn (int $id, int $position): string => "WHEN {$id} THEN {$position}")
            ->implode(' ');

        $builder->orderByRaw("CASE products.id {$orderCases} ELSE 2147483647 END");
    }

    /** @param array<string, mixed> $filters */
    private function logQuery(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::withoutGlobalScope(StoreScope::class)->create([
            'store_id' => $store->id,
            'query' => Str::limit(Str::squish($query), 255, ''),
            'filters_json' => $filters === [] ? null : $filters,
            'results_count' => $resultsCount,
        ]);
    }
}
