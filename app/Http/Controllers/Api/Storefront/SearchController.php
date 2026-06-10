<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\InventoryPolicy;
use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SearchController extends Controller
{
    public function __construct(protected SearchService $search) {}

    /**
     * GET /api/storefront/v1/search (spec 02 section 2.5).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'filters' => ['sometimes', 'json'],
            'sort' => ['sometimes', 'string', 'in:relevance,price_asc,price_desc,newest,best_selling'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $store = app('current_store');
        $filters = $this->normalizeFilters(json_decode($validated['filters'] ?? '{}', true) ?: []);

        $results = $this->search->search(
            $store,
            $validated['q'],
            $filters,
            (int) ($validated['per_page'] ?? 24),
            $validated['sort'] ?? 'relevance',
            'page',
            isset($validated['page']) ? (int) $validated['page'] : null,
        );

        /** @var \Illuminate\Database\Eloquent\Collection<int, Product> $products */
        $products = $results->getCollection();

        return response()->json([
            'query' => $validated['q'],
            'results' => $products->map(fn (Product $product): array => $this->serializeProduct($product))->values(),
            'facets' => $this->facets($products),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/storefront/v1/search/suggest (spec 02 section 2.5).
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $store = app('current_store');
        $limit = (int) ($validated['limit'] ?? 5);

        $products = $this->search->autocomplete($store, $validated['q'], $limit);

        $collections = Collection::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->published()
            ->where('title', 'like', trim($validated['q']).'%')
            ->limit($limit)
            ->get();

        $suggestions = $products
            ->map(fn (Product $product): array => [
                'type' => 'product',
                'title' => $product->title,
                'handle' => $product->handle,
                'image_url' => $this->imageUrl($product),
                'price_amount' => $this->displayVariant($product)?->price_amount ?? 0,
                'currency' => $this->currency($product),
            ])
            ->concat($collections->map(fn (Collection $collection): array => [
                'type' => 'collection',
                'title' => $collection->title,
                'handle' => $collection->handle,
                'image_url' => null,
            ]))
            ->values();

        return response()->json([
            'query' => $validated['q'],
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Map the spec 02 filters JSON schema onto SearchService filter keys.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function normalizeFilters(array $filters): array
    {
        return array_filter([
            'vendor' => $filters['vendor'] ?? null,
            'collection_id' => $filters['collection_id'] ?? null,
            'price_min' => $filters['price_min'] ?? null,
            'price_max' => $filters['price_max'] ?? null,
            'in_stock' => ($filters['in_stock'] ?? false) === true ? true : null,
            'tags' => $filters['tags'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeProduct(Product $product): array
    {
        $variant = $this->displayVariant($product);

        return [
            'id' => $product->getKey(),
            'title' => $product->title,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'price_amount' => $variant?->price_amount ?? 0,
            'compare_at_amount' => $variant?->compare_at_amount,
            'currency' => $this->currency($product),
            'image_url' => $this->imageUrl($product),
            'in_stock' => $this->isInStock($product),
            'tags' => $product->tags ?? [],
        ];
    }

    /**
     * Facets over the current result page: vendor counts, tag counts, and
     * the price range across default variants.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    protected function facets($products): array
    {
        $vendors = $products
            ->filter(fn (Product $product): bool => filled($product->vendor))
            ->countBy('vendor')
            ->map(fn (int $count, string $vendor): array => ['value' => $vendor, 'count' => $count])
            ->values();

        $tags = $products
            ->flatMap(fn (Product $product): array => $product->tags ?? [])
            ->countBy()
            ->map(fn (int $count, string $tag): array => ['value' => $tag, 'count' => $count])
            ->values();

        $prices = $products
            ->map(fn (Product $product): ?int => $this->displayVariant($product)?->price_amount)
            ->filter(fn (?int $price): bool => $price !== null);

        return [
            'vendors' => $vendors,
            'tags' => $tags,
            'price_range' => [
                'min' => $prices->isEmpty() ? 0 : $prices->min(),
                'max' => $prices->isEmpty() ? 0 : $prices->max(),
            ],
        ];
    }

    protected function displayVariant(Product $product): ?ProductVariant
    {
        return $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    }

    protected function currency(Product $product): string
    {
        return $this->displayVariant($product)?->currency
            ?? (app('current_store')->default_currency ?? 'EUR');
    }

    protected function imageUrl(Product $product): ?string
    {
        $media = $product->media->first();

        return $media !== null ? Storage::disk('public')->url($media->storage_key) : null;
    }

    protected function isInStock(Product $product): bool
    {
        if ($product->variants->isEmpty()) {
            return false;
        }

        return $product->variants->contains(function (ProductVariant $variant): bool {
            $inventory = $variant->inventoryItem;

            return $inventory === null
                || $inventory->availableQuantity() > 0
                || $inventory->policy === InventoryPolicy::Continue;
        });
    }
}
