<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Search\SearchRequest;
use App\Http\Requests\Storefront\Search\SearchSuggestRequest;
use App\Models\Collection;
use App\Models\Product;
use App\Models\SearchQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SearchController extends Controller
{
    use ResolvesApiContext;

    public function search(SearchRequest $request): JsonResponse
    {
        $storeId = $this->currentStoreId($request);
        $validated = $request->validated();

        $queryTerm = trim((string) ($validated['q'] ?? $validated['query'] ?? ''));
        $sort = (string) ($validated['sort'] ?? 'relevance');
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 24);

        $filters = $this->parseFilters($validated['filters'] ?? null);

        if ($filters === null) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'filters' => ['Filters must be a JSON object or an object-like query parameter.'],
                ],
            ], 422);
        }

        $productsQuery = Product::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where(function (Builder $builder) use ($queryTerm): void {
                $like = '%'.$queryTerm.'%';

                $builder->where('title', 'like', $like)
                    ->orWhere('handle', 'like', $like)
                    ->orWhere('vendor', 'like', $like)
                    ->orWhere('product_type', 'like', $like);
            });

        $this->applyFilters($productsQuery, $filters);

        if ($sort === 'price_asc' || $sort === 'price_desc') {
            $productsQuery->withMin(['variants as min_price_amount' => fn (Builder $query) => $query->where('status', 'active')], 'price_amount')
                ->orderBy('min_price_amount', $sort === 'price_asc' ? 'asc' : 'desc');
        } elseif ($sort === 'newest') {
            $productsQuery->orderByDesc('published_at')->orderByDesc('id');
        } elseif ($sort === 'best_selling') {
            $productsQuery->orderByDesc('updated_at')->orderByDesc('id');
        } elseif ($sort === 'title_asc') {
            $productsQuery->orderBy('title');
        } else {
            $productsQuery->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$queryTerm.'%'])
                ->orderBy('title');
        }

        $productsQuery->with([
            'media' => fn ($query) => $query->orderBy('position'),
            'variants' => fn ($query) => $query->where('status', 'active')->with('inventoryItem')->orderBy('price_amount'),
        ]);

        $paginator = $productsQuery->paginate($perPage, ['*'], 'page', $page);

        $results = collect($paginator->items())->map(function (Product $product): array {
            $variant = $product->variants->first();
            $media = $product->media->first();

            $inStock = $product->variants->contains(function ($variant): bool {
                $inventory = $variant->inventoryItem;

                if ($inventory === null) {
                    return true;
                }

                if ($this->enumValue($inventory->policy) === 'continue') {
                    return true;
                }

                return ((int) $inventory->quantity_on_hand - (int) $inventory->quantity_reserved) > 0;
            });

            return [
                'id' => (int) $product->id,
                'title' => (string) $product->title,
                'handle' => (string) $product->handle,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'price_amount' => $variant !== null ? (int) $variant->price_amount : 0,
                'compare_at_amount' => $variant?->compare_at_amount === null ? null : (int) $variant->compare_at_amount,
                'currency' => $variant?->currency,
                'image_url' => $this->imageUrl($media?->storage_key),
                'in_stock' => $inStock,
                'tags' => is_array($product->tags) ? $product->tags : [],
            ];
        })->values();

        $vendorCounts = [];

        foreach ($results as $result) {
            $vendor = $result['vendor'];

            if (! is_string($vendor) || $vendor === '') {
                continue;
            }

            $vendorCounts[$vendor] = ($vendorCounts[$vendor] ?? 0) + 1;
        }

        arsort($vendorCounts);

        $vendorsFacet = [];

        foreach ($vendorCounts as $vendor => $count) {
            $vendorsFacet[] = [
                'value' => $vendor,
                'count' => $count,
            ];
        }

        $tagCounts = [];
        $priceMin = null;
        $priceMax = null;

        foreach ($results as $result) {
            $price = (int) ($result['price_amount'] ?? 0);
            $priceMin = $priceMin === null ? $price : min($priceMin, $price);
            $priceMax = $priceMax === null ? $price : max($priceMax, $price);

            $tags = $result['tags'];

            if (! is_array($tags)) {
                continue;
            }

            foreach ($tags as $tag) {
                if (! is_string($tag) || $tag === '') {
                    continue;
                }

                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }

        arsort($tagCounts);

        $tagsFacet = [];

        foreach ($tagCounts as $tag => $count) {
            $tagsFacet[] = [
                'value' => $tag,
                'count' => $count,
            ];
        }

        SearchQuery::query()->create([
            'store_id' => $storeId,
            'query' => $queryTerm,
            'filters_json' => $filters,
            'results_count' => (int) $paginator->total(),
            'created_at' => now(),
        ]);

        return response()->json([
            'query' => $queryTerm,
            'data' => $results->all(),
            'results' => $results->all(),
            'facets' => [
                'vendors' => $vendorsFacet,
                'tags' => $tagsFacet,
                'price_range' => [
                    'min' => $priceMin ?? 0,
                    'max' => $priceMax ?? 0,
                ],
            ],
            'pagination' => [
                'current_page' => (int) $paginator->currentPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'last_page' => (int) $paginator->lastPage(),
            ],
        ]);
    }

    public function suggest(SearchSuggestRequest $request): JsonResponse
    {
        $storeId = $this->currentStoreId($request);
        $validated = $request->validated();

        $queryTerm = trim((string) ($validated['q'] ?? $validated['query'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 5);

        $products = Product::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where(function (Builder $builder) use ($queryTerm): void {
                $prefix = $queryTerm.'%';

                $builder->where('title', 'like', $prefix)
                    ->orWhere('handle', 'like', $prefix);
            })
            ->with([
                'media' => fn ($query) => $query->orderBy('position'),
                'variants' => fn ($query) => $query->where('status', 'active')->orderBy('price_amount'),
            ])
            ->limit($limit)
            ->get();

        $collections = Collection::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where(function (Builder $builder) use ($queryTerm): void {
                $prefix = $queryTerm.'%';

                $builder->where('title', 'like', $prefix)
                    ->orWhere('handle', 'like', $prefix);
            })
            ->limit($limit)
            ->get();

        $suggestions = [];

        foreach ($products as $product) {
            if (count($suggestions) >= $limit) {
                break;
            }

            $variant = $product->variants->first();
            $media = $product->media->first();

            $suggestions[] = [
                'type' => 'product',
                'title' => (string) $product->title,
                'handle' => (string) $product->handle,
                'image_url' => $this->imageUrl($media?->storage_key),
                'price_amount' => $variant !== null ? (int) $variant->price_amount : null,
                'currency' => $variant?->currency,
            ];
        }

        foreach ($collections as $collection) {
            if (count($suggestions) >= $limit) {
                break;
            }

            $suggestions[] = [
                'type' => 'collection',
                'title' => (string) $collection->title,
                'handle' => (string) $collection->handle,
                'image_url' => null,
                'price_amount' => null,
                'currency' => null,
            ];
        }

        return response()->json([
            'query' => $queryTerm,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    private function applyFilters(Builder $query, ?array $filters): void
    {
        if (! is_array($filters)) {
            return;
        }

        if (isset($filters['collection_id']) && is_numeric($filters['collection_id'])) {
            $collectionId = (int) $filters['collection_id'];

            $query->whereHas('collections', fn (Builder $builder) => $builder->where('collections.id', $collectionId));
        }

        if (isset($filters['vendor']) && is_string($filters['vendor']) && $filters['vendor'] !== '') {
            $query->where('vendor', $filters['vendor']);
        }

        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $priceMin = (int) $filters['price_min'];
            $query->whereHas('variants', fn (Builder $builder) => $builder->where('price_amount', '>=', $priceMin));
        }

        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $priceMax = (int) $filters['price_max'];
            $query->whereHas('variants', fn (Builder $builder) => $builder->where('price_amount', '<=', $priceMax));
        }

        if (($filters['in_stock'] ?? false) === true) {
            $query->whereHas('variants', function (Builder $builder): void {
                $builder->where(function (Builder $variantQuery): void {
                    $variantQuery->whereHas('inventoryItem', function (Builder $inventoryQuery): void {
                        $inventoryQuery->where('policy', 'continue')
                            ->orWhereRaw('quantity_on_hand > quantity_reserved');
                    })->orWhereDoesntHave('inventoryItem');
                });
            });
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                if (! is_string($tag) || $tag === '') {
                    continue;
                }

                $query->where('tags', 'like', '%"'.$tag.'"%');
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseFilters(mixed $filters): ?array
    {
        if ($filters === null) {
            return [];
        }

        if (is_array($filters)) {
            return $filters;
        }

        if (! is_string($filters)) {
            return null;
        }

        if (trim($filters) === '') {
            return [];
        }

        $decoded = json_decode($filters, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function imageUrl(?string $storageKey): ?string
    {
        if ($storageKey === null || $storageKey === '') {
            return null;
        }

        if (str_starts_with($storageKey, 'http://') || str_starts_with($storageKey, 'https://')) {
            return $storageKey;
        }

        try {
            return Storage::disk((string) config('filesystems.default', 'public'))->url($storageKey);
        } catch (\Throwable) {
            return $storageKey;
        }
    }
}
