<?php

namespace App\Services;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 24, string $sort = 'relevance'): LengthAwarePaginator
    {
        $query = trim($query);
        $perPage = max(1, min(50, $perPage));

        if ($query === '') {
            return $this->browse($store, $filters, $perPage, $sort);
        }

        $paginator = $this->baseSearchQuery($store, $query, $filters)
            ->tap(fn (QueryBuilder $builder) => $this->applySort($builder, $sort, true))
            ->paginate($perPage, ['products.id'], 'page', Paginator::resolveCurrentPage());

        $paginator->setCollection($this->hydrateProducts(
            collect($paginator->items())->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()
        ));

        $this->log($store, $query, $filters, $paginator->total());

        return $paginator;
    }

    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);
        $limit = max(1, min(10, $limit));

        if ($prefix === '') {
            return collect();
        }

        $ids = $this->baseSearchQuery($store, $prefix, [])
            ->orderByRaw('products_fts.rank')
            ->limit($limit)
            ->pluck('products.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return $this->hydrateProducts($ids);
    }

    public function suggestions(Store $store, string $prefix, int $limit = 5): Collection
    {
        $productLimit = max(1, min(10, $limit));

        $products = $this->autocomplete($store, $prefix, $productLimit)
            ->map(function (Product $product): array {
                $variant = $product->variants->first();

                return [
                    'type' => 'product',
                    'title' => $product->title,
                    'handle' => $product->handle,
                    'image_url' => $product->media->first()?->storage_key,
                    'price_amount' => $variant?->price_amount,
                    'currency' => $variant?->currency ?? $product->store?->default_currency,
                ];
            });

        $remaining = max(0, $productLimit - $products->count());

        $collections = $remaining > 0
            ? ProductCollection::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->where('status', 'active')
                ->where('title', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], trim($prefix)).'%')
                ->oldest('title')
                ->limit($remaining)
                ->get()
                ->map(fn (ProductCollection $collection): array => [
                    'type' => 'collection',
                    'title' => $collection->title,
                    'handle' => $collection->handle,
                    'image_url' => null,
                ])
            : collect();

        return $products->concat($collections)->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{vendors: list<array{value: string, count: int}>, tags: list<array{value: string, count: int}>, price_range: array{min: int|null, max: int|null}}
     */
    public function facets(Store $store, string $query, array $filters = []): array
    {
        $ids = trim($query) === ''
            ? $this->activeProductQuery($store, $filters)->limit(1000)->pluck('products.id')->all()
            : $this->baseSearchQuery($store, $query, $filters)->limit(1000)->pluck('products.id')->all();

        $ids = collect($ids)->map(fn (mixed $id): int => (int) $id)->all();

        if ($ids === []) {
            return [
                'vendors' => [],
                'tags' => [],
                'price_range' => ['min' => null, 'max' => null],
            ];
        }

        $vendors = Product::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->whereNotNull('vendor')
            ->selectRaw('vendor as value, COUNT(*) as count')
            ->groupBy('vendor')
            ->orderBy('vendor')
            ->get()
            ->map(fn (Product $product): array => [
                'value' => (string) $product->value,
                'count' => (int) $product->count,
            ])
            ->all();

        $tags = Product::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->get(['tags'])
            ->flatMap(fn (Product $product): array => $product->tags ?? [])
            ->filter()
            ->countBy()
            ->sortKeys()
            ->map(fn (int $count, string $tag): array => [
                'value' => $tag,
                'count' => $count,
            ])
            ->values()
            ->all();

        $priceRange = ProductVariant::withoutGlobalScopes()
            ->whereIn('product_id', $ids)
            ->selectRaw('MIN(price_amount) as min_price, MAX(price_amount) as max_price')
            ->first();

        return [
            'vendors' => $vendors,
            'tags' => $tags,
            'price_range' => [
                'min' => $priceRange?->min_price === null ? null : (int) $priceRange->min_price,
                'max' => $priceRange?->max_price === null ? null : (int) $priceRange->max_price,
            ],
        ];
    }

    public function syncProduct(Product $product): void
    {
        DB::table('products_fts')
            ->where('product_id', $product->getKey())
            ->delete();

        DB::table('products_fts')->insert([
            'store_id' => $product->store_id,
            'product_id' => $product->getKey(),
            'title' => $product->title,
            'description' => trim(strip_tags((string) $product->description_html)),
            'vendor' => (string) $product->vendor,
            'product_type' => (string) $product->product_type,
            'tags' => collect(Arr::wrap($product->tags))->filter()->implode(' '),
        ]);
    }

    public function removeProduct(int $productId): void
    {
        DB::table('products_fts')
            ->where('product_id', $productId)
            ->delete();
    }

    public function reindex(Store $store): int
    {
        DB::table('products_fts')
            ->where('store_id', $store->getKey())
            ->delete();

        $count = 0;

        Product::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->orderBy('id')
            ->chunkById(100, function (Collection $products) use (&$count): void {
                $products->each(function (Product $product) use (&$count): void {
                    $this->syncProduct($product);
                    $count++;
                });
            });

        return $count;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function browse(Store $store, array $filters, int $perPage, string $sort): LengthAwarePaginator
    {
        $paginator = $this->activeProductQuery($store, $filters)
            ->tap(fn (QueryBuilder $builder) => $this->applySort($builder, $sort, false))
            ->paginate($perPage, ['products.id'], 'page', Paginator::resolveCurrentPage());

        $paginator->setCollection($this->hydrateProducts(
            collect($paginator->items())->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()
        ));

        return $paginator;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseSearchQuery(Store $store, string $query, array $filters): QueryBuilder
    {
        $match = $this->toFtsQuery($store, $query);

        if ($match === '') {
            return $this->activeProductQuery($store, $filters);
        }

        return $this->activeProductQuery($store, $filters)
            ->join('products_fts', 'products_fts.product_id', '=', 'products.id')
            ->where('products_fts.store_id', $store->getKey())
            ->whereRaw('products_fts MATCH ?', [$match]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function activeProductQuery(Store $store, array $filters): QueryBuilder
    {
        $builder = DB::table('products')
            ->select('products.id')
            ->where('products.store_id', $store->getKey())
            ->where('products.status', ProductStatus::Active->value)
            ->whereNotNull('products.published_at');

        $this->applyFilters($builder, $filters);

        return $builder;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(QueryBuilder $builder, array $filters): void
    {
        if (isset($filters['collection_id'])) {
            $builder->whereExists(function (QueryBuilder $query) use ($filters): void {
                $query
                    ->selectRaw('1')
                    ->from('collection_products')
                    ->whereColumn('collection_products.product_id', 'products.id')
                    ->where('collection_products.collection_id', (int) $filters['collection_id']);
            });
        }

        $vendors = collect(Arr::wrap($filters['vendor'] ?? []))
            ->filter(fn (mixed $vendor): bool => filled($vendor))
            ->map(fn (mixed $vendor): string => (string) $vendor)
            ->values()
            ->all();

        if ($vendors !== []) {
            $builder->whereIn('products.vendor', $vendors);
        }

        $productTypes = collect(Arr::wrap($filters['product_type'] ?? []))
            ->filter(fn (mixed $productType): bool => filled($productType))
            ->map(fn (mixed $productType): string => (string) $productType)
            ->values()
            ->all();

        if ($productTypes !== []) {
            $builder->whereIn('products.product_type', $productTypes);
        }

        foreach (Arr::wrap($filters['tags'] ?? []) as $tag) {
            if (filled($tag)) {
                $builder->where('products.tags', 'like', '%"'.str_replace(['%', '_'], ['\%', '\_'], (string) $tag).'"%');
            }
        }

        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $builder->whereExists(function (QueryBuilder $query) use ($filters): void {
                $query
                    ->selectRaw('1')
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id');

                if (isset($filters['price_min'])) {
                    $query->where('product_variants.price_amount', '>=', (int) $filters['price_min']);
                }

                if (isset($filters['price_max'])) {
                    $query->where('product_variants.price_amount', '<=', (int) $filters['price_max']);
                }
            });
        }

        if (filter_var($filters['in_stock'] ?? false, FILTER_VALIDATE_BOOL)) {
            $builder->whereExists(function (QueryBuilder $query): void {
                $query
                    ->selectRaw('1')
                    ->from('product_variants')
                    ->join('inventory_items', 'inventory_items.variant_id', '=', 'product_variants.id')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where(function (QueryBuilder $query): void {
                        $query
                            ->whereRaw('inventory_items.quantity_on_hand > inventory_items.quantity_reserved')
                            ->orWhere('inventory_items.policy', InventoryPolicy::Continue->value);
                    });
            });
        }
    }

    private function applySort(QueryBuilder $builder, string $sort, bool $hasRank): void
    {
        match ($sort) {
            'price_asc' => $builder
                ->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) ASC')
                ->orderBy('products.id'),
            'price_desc' => $builder
                ->orderByRaw('(SELECT MIN(price_amount) FROM product_variants WHERE product_variants.product_id = products.id) DESC')
                ->orderBy('products.id'),
            'newest' => $builder
                ->orderByDesc('products.published_at')
                ->orderByDesc('products.id'),
            'best_selling' => $builder
                ->orderByRaw('(SELECT COALESCE(SUM(order_lines.quantity), 0) FROM order_lines WHERE order_lines.product_id = products.id) DESC')
                ->orderByDesc('products.published_at')
                ->orderByDesc('products.id'),
            default => $hasRank
                ? $builder->orderByRaw('products_fts.rank')
                : $builder->orderByDesc('products.published_at')->orderByDesc('products.id'),
        };
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    private function hydrateProducts(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $products = Product::withoutGlobalScopes()
            ->with(['store', 'variants.inventoryItem', 'media'])
            ->withCount('variants')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?Product => $products->get($id))
            ->filter()
            ->values();
    }

    private function toFtsQuery(Store $store, string $query): string
    {
        $settings = $this->settings($store);
        $stopWords = collect($settings?->stop_words_json ?? [])
            ->map(fn (mixed $word): string => (string) $word)
            ->flatMap(fn (string $word): array => $this->tokens($word))
            ->unique()
            ->values();
        $synonyms = $this->synonymExpressions($settings);
        $tokens = collect($this->tokens($query))
            ->reject(fn (string $token): bool => $stopWords->contains($token))
            ->take(8)
            ->values()
            ->all();
        $lastIndex = count($tokens) - 1;

        return collect($tokens)
            ->map(function (string $token, int $index) use ($lastIndex, $synonyms): string {
                $prefix = $index === $lastIndex;
                $expressions = $synonyms[$token] ?? [$this->termExpression([$token], $prefix)];

                if (count($expressions) === 1) {
                    return $expressions[0];
                }

                return '('.implode(' OR ', $expressions).')';
            })
            ->implode(' AND ');
    }

    /**
     * @return list<string>
     */
    private function tokens(string $value): array
    {
        return preg_split('/[^\pL\pN]+/u', mb_strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @return array<string, list<string>>
     */
    private function synonymExpressions(?SearchSettings $settings): array
    {
        $synonyms = [];

        foreach ($settings?->synonyms_json ?? [] as $group) {
            $terms = collect(Arr::wrap($group))
                ->map(fn (mixed $term): array => $this->tokens((string) $term))
                ->filter()
                ->values();
            $expressions = $terms
                ->map(fn (array $tokens): string => $this->termExpression($tokens, true))
                ->unique()
                ->values()
                ->all();

            if (count($expressions) < 2) {
                continue;
            }

            foreach ($terms as $tokens) {
                foreach ($tokens as $token) {
                    $synonyms[$token] = collect($synonyms[$token] ?? [])
                        ->merge($expressions)
                        ->unique()
                        ->values()
                        ->all();
                }
            }
        }

        return $synonyms;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function termExpression(array $tokens, bool $prefix): string
    {
        if (count($tokens) === 1) {
            return $tokens[0].($prefix ? '*' : '');
        }

        return '"'.implode(' ', $tokens).'"';
    }

    private function settings(Store $store): ?SearchSettings
    {
        return SearchSettings::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function log(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            'query' => mb_substr($query, 0, 200),
            'filters_json' => $filters,
            'results_count' => $resultsCount,
            'created_at' => now(),
        ]);
    }
}
