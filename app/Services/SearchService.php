<?php

namespace App\Services;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(Store $store, string $query, array $filters = [], int $perPage = 24, string $sort = 'relevance'): LengthAwarePaginator
    {
        $normalizedQuery = trim($query);
        $match = $this->matchQuery($store, $normalizedQuery);
        $rankedIds = $match ? $this->matchingProductIds($store, $match) : collect();

        if ($normalizedQuery !== '' && $rankedIds->isEmpty()) {
            $paginator = $this->emptyPaginator($perPage);
            $this->logQuery($store, $normalizedQuery, $filters, $paginator->total());

            return $paginator;
        }

        $products = $this->visibleProductQuery($store)
            ->with([
                'media' => fn ($query) => $query->oldest('position'),
                'variants' => fn ($query) => $query
                    ->with('inventoryItem')
                    ->where('status', VariantStatus::Active)
                    ->orderByDesc('is_default')
                    ->oldest('position'),
            ]);

        if ($rankedIds->isNotEmpty()) {
            $products->whereIn((new Product)->getTable().'.id', $rankedIds->all());
        }

        $this->applyFilters($products, $filters);
        $this->applySort($products, $sort, $rankedIds);

        $paginator = $products->paginate($perPage)->withQueryString();

        if ($normalizedQuery !== '') {
            $this->logQuery($store, $normalizedQuery, $filters, $paginator->total());
        }

        return $paginator;
    }

    /**
     * @return Collection<int, array{type: string, title: string, subtitle: string|null, url: string}>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): Collection
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return collect();
        }

        $match = $this->matchQuery($store, $prefix);
        $productIds = $match ? $this->matchingProductIds($store, $match, $limit) : collect();

        $products = $productIds->isEmpty()
            ? collect()
            : $this->visibleProductQuery($store)
                ->whereIn((new Product)->getTable().'.id', $productIds->all())
                ->get()
                ->sortBy(fn (Product $product): int => $productIds->search($product->id))
                ->values()
                ->map(fn (Product $product): array => [
                    'type' => 'product',
                    'title' => $product->title,
                    'subtitle' => $product->vendor,
                    'url' => route('storefront.products.show', $product->handle),
                ]);

        $remaining = max(0, $limit - $products->count());
        $collections = $remaining === 0 ? collect() : ProductCollection::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', CollectionStatus::Active)
            ->where(function (Builder $query) use ($prefix): void {
                $term = $this->likeTerm($prefix);
                $query->where('title', 'like', $term)
                    ->orWhere('handle', 'like', $term);
            })
            ->oldest('title')
            ->limit($remaining)
            ->get()
            ->map(fn (ProductCollection $collection): array => [
                'type' => 'collection',
                'title' => $collection->title,
                'subtitle' => 'Collection',
                'url' => route('storefront.collections.show', $collection->handle),
            ]);

        return $products->concat($collections)->values();
    }

    public function syncProduct(Product $product): void
    {
        $product = Product::withoutGlobalScopes()->find($product->id);

        if (! $product instanceof Product) {
            return;
        }

        $this->removeProduct($product->id);

        DB::insert(
            'INSERT INTO products_fts(rowid, store_id, product_id, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $product->id,
                $product->store_id,
                $product->id,
                $product->title ?? '',
                strip_tags($product->description_html ?? ''),
                $product->vendor ?? '',
                $product->product_type ?? '',
                implode(' ', $product->tags ?? []),
            ],
        );
    }

    public function removeProduct(int $productId): void
    {
        DB::delete('DELETE FROM products_fts WHERE product_id = ?', [$productId]);
    }

    public function reindexStore(Store $store): int
    {
        DB::delete('DELETE FROM products_fts WHERE store_id = ?', [$store->id]);

        $count = 0;

        Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->orderBy('id')
            ->each(function (Product $product) use (&$count): void {
                $this->syncProduct($product);
                $count++;
            });

        SearchSettings::query()->firstOrCreate(['store_id' => $store->id])->touch();

        return $count;
    }

    /**
     * @return array{store_id: int, index_status: string, last_reindex_at: string|null, last_reindex_duration_seconds: int|null, documents_count: int, pending_updates: int}
     */
    public function indexStatus(Store $store): array
    {
        $settings = SearchSettings::query()
            ->where('store_id', $store->id)
            ->first();
        $documentsCount = (int) DB::table('products_fts')
            ->where('store_id', $store->id)
            ->count();
        $productsCount = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->count();
        $pendingUpdates = max(0, $productsCount - $documentsCount);

        return [
            'store_id' => $store->id,
            'index_status' => $pendingUpdates === 0 ? 'ready' : 'pending',
            'last_reindex_at' => $settings?->updated_at?->toISOString(),
            'last_reindex_duration_seconds' => $settings?->updated_at === null ? null : 0,
            'documents_count' => $documentsCount,
            'pending_updates' => $pendingUpdates,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function facets(Store $store, string $query = ''): array
    {
        $match = $this->matchQuery($store, trim($query));
        $rankedIds = $match ? $this->matchingProductIds($store, $match, 1000) : collect();

        if (trim($query) !== '' && ($match === null || $rankedIds->isEmpty())) {
            return $this->emptyFacets();
        }

        $products = $this->visibleProductQuery($store)
            ->with(['variants' => fn ($query) => $query->where('status', VariantStatus::Active), 'variants.inventoryItem'])
            ->when($rankedIds->isNotEmpty(), fn (Builder $query) => $query->whereIn((new Product)->getTable().'.id', $rankedIds->all()))
            ->get();

        $prices = $products
            ->flatMap(fn (Product $product) => $product->variants->pluck('price_amount'))
            ->filter(fn (int $price): bool => $price > 0);

        return [
            'vendors' => $products
                ->pluck('vendor')
                ->filter()
                ->countBy()
                ->sortKeys()
                ->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])
                ->values()
                ->all(),
            'product_types' => $products
                ->pluck('product_type')
                ->filter()
                ->countBy()
                ->sortKeys()
                ->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])
                ->values()
                ->all(),
            'tags' => $products
                ->flatMap(fn (Product $product): array => $product->tags ?? [])
                ->filter()
                ->countBy()
                ->sortKeys()
                ->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])
                ->values()
                ->all(),
            'price' => [
                'min' => $prices->min(),
                'max' => $prices->max(),
            ],
        ];
    }

    /**
     * @return Builder<Product>
     */
    private function visibleProductQuery(Store $store): Builder
    {
        return Product::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at');
    }

    /**
     * @param  Builder<Product>  $products
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $products, array $filters): void
    {
        $vendor = trim((string) ($filters['vendor'] ?? ''));
        $productType = trim((string) ($filters['product_type'] ?? ''));
        $collectionId = (int) ($filters['collection_id'] ?? 0);
        $minimumPrice = $this->integerFilter($filters['price_min'] ?? null);
        $maximumPrice = $this->integerFilter($filters['price_max'] ?? null);
        $inStock = filter_var($filters['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $tags = collect((array) ($filters['tags'] ?? []))
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->filter()
            ->values();

        if ($vendor !== '') {
            $products->where('vendor', $vendor);
        }

        if ($productType !== '') {
            $products->where('product_type', $productType);
        }

        if ($collectionId > 0) {
            $products->whereHas('collections', fn (Builder $query) => $query->whereKey($collectionId));
        }

        if ($minimumPrice !== null || $maximumPrice !== null) {
            $products->whereHas('variants', function (Builder $query) use ($minimumPrice, $maximumPrice): void {
                $query->where('status', VariantStatus::Active);

                if ($minimumPrice !== null) {
                    $query->where('price_amount', '>=', $minimumPrice);
                }

                if ($maximumPrice !== null) {
                    $query->where('price_amount', '<=', $maximumPrice);
                }
            });
        }

        if ($inStock) {
            $products->whereHas('variants', function (Builder $query): void {
                $query->where('status', VariantStatus::Active)
                    ->whereHas('inventoryItem', function (Builder $query): void {
                        $query->whereRaw('quantity_on_hand - quantity_reserved > 0');
                    });
            });
        }

        foreach ($tags as $tag) {
            $products->whereJsonContains('tags', $tag);
        }
    }

    /**
     * @param  Builder<Product>  $products
     * @param  Collection<int, int>  $rankedIds
     */
    private function applySort(Builder $products, string $sort, Collection $rankedIds): void
    {
        if ($sort === 'price_asc' || $sort === 'price_desc') {
            $products
                ->withMin(['variants as search_price_amount' => fn (Builder $query) => $query->where('status', VariantStatus::Active)], 'price_amount')
                ->orderBy('search_price_amount', $sort === 'price_asc' ? 'asc' : 'desc')
                ->orderBy('title');

            return;
        }

        if ($sort === 'newest') {
            $products->latest('published_at')->latest('id');

            return;
        }

        if ($rankedIds->isNotEmpty()) {
            $case = $rankedIds
                ->values()
                ->map(fn (int $id, int $position): string => 'WHEN '.$id.' THEN '.$position)
                ->implode(' ');

            $products->orderByRaw('CASE '.(new Product)->getTable().".id {$case} ELSE 999999 END");

            return;
        }

        $products->latest('published_at')->latest('id');
    }

    private function integerFilter(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function likeTerm(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($value)).'%';
    }

    private function matchQuery(Store $store, string $query): ?string
    {
        $tokens = $this->tokens($query);

        if ($tokens->isEmpty()) {
            return null;
        }

        $settings = SearchSettings::query()->where('store_id', $store->id)->first();
        $stopWords = collect($settings?->stop_words_json ?? [])
            ->map(fn (mixed $word) => $this->tokens((string) $word)->first())
            ->filter()
            ->flip();
        $synonyms = $this->synonymMap($settings?->synonyms_json ?? []);

        $tokens = $tokens
            ->reject(fn (string $token): bool => $stopWords->has($token))
            ->values();

        if ($tokens->isEmpty()) {
            return null;
        }

        $lastIndex = $tokens->count() - 1;

        return $tokens
            ->map(function (string $token, int $index) use ($synonyms, $lastIndex): string {
                $suffix = $index === $lastIndex ? '*' : '';
                $terms = collect([$token])
                    ->concat($synonyms[$token] ?? [])
                    ->unique()
                    ->map(fn (string $term): string => $term.$suffix)
                    ->values();

                return $terms->count() > 1
                    ? '('.$terms->implode(' OR ').')'
                    : (string) $terms->first();
            })
            ->implode(' ');
    }

    /**
     * @return Collection<int, string>
     */
    private function tokens(string $query): Collection
    {
        preg_match_all('/[\p{L}\p{N}_]+/u', Str::lower(Str::ascii($query)), $matches);

        return collect($matches[0] ?? [])
            ->map(fn (string $token): string => trim($token))
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, mixed>  $groups
     * @return array<string, list<string>>
     */
    private function synonymMap(array $groups): array
    {
        $map = [];

        foreach ($groups as $group) {
            $tokens = collect((array) $group)
                ->flatMap(fn (mixed $term): Collection => $this->tokens((string) $term))
                ->unique()
                ->values();

            foreach ($tokens as $token) {
                $map[$token] = $tokens
                    ->reject(fn (string $synonym): bool => $synonym === $token)
                    ->values()
                    ->all();
            }
        }

        return $map;
    }

    /**
     * @return Collection<int, int>
     */
    private function matchingProductIds(Store $store, string $match, int $limit = 500): Collection
    {
        return DB::table('products_fts')
            ->select('product_id')
            ->where('store_id', $store->id)
            ->whereRaw('products_fts MATCH ?', [$match])
            ->orderBy('rank')
            ->limit($limit)
            ->pluck('product_id')
            ->map(fn (mixed $productId): int => (int) $productId)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function logQuery(Store $store, string $query, array $filters, int $resultsCount): void
    {
        SearchQuery::query()->create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => $filters === [] ? null : $filters,
            'results_count' => $resultsCount,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    private function emptyPaginator(int $perPage): LengthAwarePaginator
    {
        return new Paginator(
            collect(),
            0,
            $perPage,
            Paginator::resolveCurrentPage(),
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyFacets(): array
    {
        return [
            'vendors' => [],
            'product_types' => [],
            'tags' => [],
            'price' => [
                'min' => null,
                'max' => null,
            ],
        ];
    }
}
