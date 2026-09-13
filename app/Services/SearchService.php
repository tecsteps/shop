<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\SearchSetting;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * SQLite FTS5 product search per spec 05 section 16.
 */
class SearchService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{results: LengthAwarePaginator, facets: array{vendors: array, tags: array, price_range: array{min: int, max: int}}}
     */
    public function search(Store $store, string $query, array $filters = [], string $sort = 'relevance', int $perPage = 24, int $page = 1): array
    {
        $rankedIds = $this->matchProductIds($store->id, $query);

        $products = $this->loadSearchableProducts($store, $rankedIds);
        $products = $this->applyFilters($products, $filters);
        $products = $this->applySort($products, $sort, $rankedIds);

        SearchQuery::query()->create([
            'store_id' => $store->id,
            'query' => $query,
            'filters_json' => $filters === [] ? null : $filters,
            'results_count' => $products->count(),
            'created_at' => now(),
        ]);

        $facets = $this->facets($products);
        $items = $products->map(fn (Product $product): array => $this->resultRow($product, $store))->all();

        $paginator = new LengthAwarePaginator(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            count($items),
            $perPage,
            $page,
        );

        return ['results' => $paginator, 'facets' => $facets];
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function autocomplete(Store $store, string $prefix, int $limit = 5): SupportCollection
    {
        $sanitized = $this->sanitize($prefix);

        $products = collect();

        if ($sanitized !== '') {
            try {
                $rows = DB::select(
                    'SELECT product_id FROM products_fts WHERE products_fts MATCH ? AND store_id = ? ORDER BY rank LIMIT ?',
                    ["{$sanitized}*", $store->id, $limit * 2]
                );
                $ids = array_map(fn ($row): int => (int) $row->product_id, $rows);

                $products = $this->loadSearchableProducts($store, $ids)->sortBy('title')->take($limit);
            } catch (\Throwable) {
                $products = collect();
            }

            if ($products->isEmpty()) {
                $products = Product::query()
                    ->where('store_id', $store->id)
                    ->where('status', ProductStatus::Active)
                    ->whereNotNull('published_at')
                    ->where('title', 'like', "{$prefix}%")
                    ->with(['productVariants', 'productMedia'])
                    ->orderBy('title')
                    ->limit($limit)
                    ->get();
            }
        }

        $collections = Collection::query()
            ->where('store_id', $store->id)
            ->where('title', 'like', "{$prefix}%")
            ->orderBy('title')
            ->limit($limit)
            ->get();

        $suggestions = $products->map(fn (Product $product): array => [
            'type' => 'product',
            'title' => $product->title,
            'handle' => $product->handle,
            'image_url' => $this->primaryImageUrl($product),
            'price_amount' => $this->displayVariant($product)?->price_amount ?? 0,
            'currency' => $this->displayVariant($product)?->currency ?? $store->default_currency,
        ])->concat($collections->map(fn (Collection $collection): array => [
            'type' => 'collection',
            'title' => $collection->title,
            'handle' => $collection->handle,
            'image_url' => null,
        ]));

        return $suggestions->take($limit)->values();
    }

    public function syncProduct(Product $product): void
    {
        DB::delete('DELETE FROM products_fts WHERE product_id = ?', [$product->getKey()]);

        DB::insert(
            'INSERT INTO products_fts (store_id, product_id, title, description, vendor, product_type, tags) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $product->store_id,
                $product->getKey(),
                (string) $product->title,
                trim(strip_tags((string) $product->description_html)),
                (string) ($product->vendor ?? ''),
                (string) ($product->product_type ?? ''),
                implode(' ', $product->tags ?? []),
            ]
        );
    }

    public function removeProduct(int $productId): void
    {
        DB::delete('DELETE FROM products_fts WHERE product_id = ?', [$productId]);
    }

    public function reindex(Store $store): int
    {
        DB::delete('DELETE FROM products_fts WHERE store_id = ?', [$store->id]);

        $count = 0;

        Product::query()->where('store_id', $store->id)->orderBy('id')->chunk(200, function ($products) use (&$count): void {
            foreach ($products as $product) {
                $this->syncProduct($product);
                $count++;
            }
        });

        $setting = SearchSetting::query()->firstOrNew(['store_id' => $store->id]);
        $setting->forceFill(['updated_at' => now()])->save();

        return $count;
    }

    public function documentCount(Store $store): int
    {
        return (int) DB::selectOne('SELECT COUNT(*) AS total FROM products_fts WHERE store_id = ?', [$store->id])->total;
    }

    /**
     * @return array<int, int> Product IDs ordered by FTS5 rank.
     */
    protected function matchProductIds(int $storeId, string $query): array
    {
        $sanitized = $this->sanitize($query);

        if ($sanitized === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $sanitized) ?: [];
        $tokens[count($tokens) - 1] .= '*';

        try {
            $rows = DB::select(
                'SELECT product_id FROM products_fts WHERE products_fts MATCH ? AND store_id = ? ORDER BY rank LIMIT 500',
                [implode(' ', $tokens), $storeId]
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map(fn ($row): int => (int) $row->product_id, $rows);
    }

    /**
     * @param  array<int, int>  $rankedIds
     * @return SupportCollection<int, Product>
     */
    protected function loadSearchableProducts(Store $store, array $rankedIds): SupportCollection
    {
        if ($rankedIds === []) {
            return collect();
        }

        return Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $rankedIds)
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->with([
                'productVariants' => fn ($query) => $query->orderBy('price_amount')->orderBy('position')->orderBy('id'),
                'productVariants.inventoryItem',
                'productMedia' => fn ($query) => $query->orderBy('position')->orderBy('id'),
                'collections',
            ])
            ->get();
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @param  array<string, mixed>  $filters
     * @return SupportCollection<int, Product>
     */
    protected function applyFilters(SupportCollection $products, array $filters): SupportCollection
    {
        if (isset($filters['collection_id'])) {
            $collectionId = (int) $filters['collection_id'];
            $products = $products->filter(
                fn (Product $product): bool => $product->collections->contains('id', $collectionId)
            );
        }

        if (isset($filters['price_min'])) {
            $min = (int) $filters['price_min'];
            $products = $products->filter(fn (Product $product): bool => $this->minPrice($product) >= $min);
        }

        if (isset($filters['price_max'])) {
            $max = (int) $filters['price_max'];
            $products = $products->filter(fn (Product $product): bool => $this->minPrice($product) <= $max);
        }

        if (! empty($filters['in_stock'])) {
            $products = $products->filter(fn (Product $product): bool => $this->inStock($product));
        }

        if (! empty($filters['tags'])) {
            $wanted = array_map(strtolower(...), (array) $filters['tags']);
            $products = $products->filter(function (Product $product) use ($wanted): bool {
                $tags = array_map(strtolower(...), $product->tags ?? []);

                return array_intersect($tags, $wanted) !== [];
            });
        }

        if (! empty($filters['vendor'])) {
            $vendor = strtolower((string) $filters['vendor']);
            $products = $products->filter(
                fn (Product $product): bool => strtolower((string) $product->vendor) === $vendor
            );
        }

        return $products->values();
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @param  array<int, int>  $rankedIds
     * @return SupportCollection<int, Product>
     */
    protected function applySort(SupportCollection $products, string $sort, array $rankedIds): SupportCollection
    {
        return match ($sort) {
            'price_asc' => $products->sortBy(fn (Product $p): array => [$this->minPrice($p), $p->id])->values(),
            'price_desc' => $products->sortBy(fn (Product $p): array => [-$this->minPrice($p), $p->id])->values(),
            'newest' => $products->sortByDesc(fn (Product $p): string => (string) $p->created_at)->values(),
            'best_selling' => $this->sortBySales($products),
            default => $products->sortBy(fn (Product $p): int => array_search($p->id, $rankedIds, true) ?: PHP_INT_MAX)->values(),
        };
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @return SupportCollection<int, Product>
     */
    protected function sortBySales(SupportCollection $products): SupportCollection
    {
        if ($products->isEmpty()) {
            return $products;
        }

        $sales = OrderLine::query()
            ->whereIn('product_id', $products->pluck('id')->all())
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) AS total')
            ->pluck('total', 'product_id');

        return $products
            ->sortBy(fn (Product $p): array => [-((int) ($sales[$p->id] ?? 0)), $p->id])
            ->values();
    }

    /**
     * @param  SupportCollection<int, Product>  $products
     * @return array{vendors: array, tags: array, price_range: array{min: int, max: int}}
     */
    protected function facets(SupportCollection $products): array
    {
        $vendors = [];
        $tags = [];
        $prices = [];

        foreach ($products as $product) {
            if ($product->vendor !== null && $product->vendor !== '') {
                $vendors[$product->vendor] = ($vendors[$product->vendor] ?? 0) + 1;
            }

            foreach ($product->tags ?? [] as $tag) {
                $tags[$tag] = ($tags[$tag] ?? 0) + 1;
            }

            $prices[] = $this->minPrice($product);
        }

        ksort($vendors);
        ksort($tags);

        return [
            'vendors' => collect($vendors)->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])->values()->all(),
            'tags' => collect($tags)->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])->values()->all(),
            'price_range' => [
                'min' => $prices === [] ? 0 : min($prices),
                'max' => $prices === [] ? 0 : max($prices),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resultRow(Product $product, Store $store): array
    {
        $variant = $this->displayVariant($product);

        return [
            'id' => $product->id,
            'title' => $product->title,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'price_amount' => $variant?->price_amount ?? 0,
            'compare_at_amount' => $variant?->compare_at_amount,
            'currency' => $variant?->currency ?? $store->default_currency,
            'image_url' => $this->primaryImageUrl($product),
            'in_stock' => $this->inStock($product),
            'tags' => $product->tags ?? [],
        ];
    }

    protected function minPrice(Product $product): int
    {
        return $this->displayVariant($product)?->price_amount ?? 0;
    }

    protected function displayVariant(Product $product): ?\App\Models\ProductVariant
    {
        $active = $product->productVariants->where('status', \App\Enums\VariantStatus::Active);

        return $active->firstWhere('is_default', true) ?? $active->sortBy(fn ($v): array => [$v->price_amount, $v->position, $v->id])->first();
    }

    protected function inStock(Product $product): bool
    {
        foreach ($product->productVariants as $variant) {
            if ($variant->status !== \App\Enums\VariantStatus::Active) {
                continue;
            }

            $item = $variant->inventoryItem;

            if ($item === null) {
                continue;
            }

            if ($item->policy === \App\Enums\InventoryPolicy::Continue) {
                return true;
            }

            if (($item->quantity_on_hand - $item->quantity_reserved) > 0) {
                return true;
            }
        }

        return false;
    }

    protected function primaryImageUrl(Product $product): ?string
    {
        $media = $product->productMedia->first();

        if ($media === null) {
            return null;
        }

        return Storage::disk('public')->url($media->storage_key);
    }

    protected function sanitize(string $query): string
    {
        $cleaned = (string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $query);
        $cleaned = (string) preg_replace('/\b(AND|OR|NOT|NEAR)\b/i', ' ', $cleaned);

        return trim((string) preg_replace('/\s+/', ' ', $cleaned));
    }
}
