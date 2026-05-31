<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Storefront search API: full-text results with facets, and lightweight
 * autocomplete suggestions. Both endpoints are public and store-scoped via the
 * resolved `current_store`. Amounts are integers in minor units (cents).
 */
class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    /**
     * Full-text product search with filters, sorting, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $store = app('current_store');

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'sort' => ['sometimes', 'string', 'in:relevance,price_asc,price_desc,newest,best_selling'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $filters = $this->parseFilters($request->query('filters'));
        $filters['sort'] = $validated['sort'] ?? 'relevance';

        $perPage = (int) ($validated['per_page'] ?? 24);
        $page = (int) ($validated['page'] ?? 1);

        $paginator = $this->search->search($store, $validated['q'], $filters, $perPage, $page);

        return response()->json([
            'query' => $validated['q'],
            'results' => $paginator->getCollection()
                ->map(fn (Product $product): array => $this->resultRow($product))
                ->values(),
            'facets' => $this->facets($paginator->getCollection()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Autocomplete suggestions as the user types.
     */
    public function suggest(Request $request): JsonResponse
    {
        $store = app('current_store');

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $limit = (int) ($validated['limit'] ?? 5);

        $suggestions = $this->search->autocomplete($store, $validated['q'], $limit)
            ->map(fn (Product $product): array => [
                'type' => 'product',
                'title' => $product->title,
                'handle' => $product->handle,
                'image_url' => $this->imageUrl($product),
                'price_amount' => $product->displayPriceAmount(),
                'currency' => $product->primaryVariant()?->currency ?? $store->default_currency,
            ])
            ->values();

        return response()->json([
            'query' => $validated['q'],
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resultRow(Product $product): array
    {
        $variant = $product->primaryVariant();

        return [
            'id' => $product->id,
            'title' => $product->title,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'price_amount' => $variant?->price_amount,
            'compare_at_amount' => $variant?->compare_at_amount,
            'currency' => $variant?->currency ?? app('current_store')->default_currency,
            'image_url' => $this->imageUrl($product),
            'in_stock' => $this->inStock($product),
            'tags' => $product->tags ?? [],
        ];
    }

    /**
     * Build vendor/tag/price facets from the current result page.
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return array<string, mixed>
     */
    private function facets($products): array
    {
        $vendors = $products
            ->filter(fn (Product $p): bool => $p->vendor !== null && $p->vendor !== '')
            ->groupBy('vendor')
            ->map(fn ($group, $vendor): array => ['value' => $vendor, 'count' => $group->count()])
            ->values();

        $tags = $products
            ->flatMap(fn (Product $p): array => $p->tags ?? [])
            ->countBy()
            ->map(fn (int $count, string $tag): array => ['value' => $tag, 'count' => $count])
            ->values();

        $prices = $products
            ->map(fn (Product $p): ?int => $p->displayPriceAmount())
            ->filter(fn (?int $price): bool => $price !== null);

        return [
            'vendors' => $vendors,
            'tags' => $tags,
            'price_range' => [
                'min' => $prices->min() ?? 0,
                'max' => $prices->max() ?? 0,
            ],
        ];
    }

    private function inStock(Product $product): bool
    {
        if (! $product->relationLoaded('variants')) {
            return true;
        }

        return $product->variants->contains(function ($variant): bool {
            $inventory = $variant->relationLoaded('inventoryItem') ? $variant->inventoryItem : null;

            return $inventory === null || $inventory->available() > 0;
        });
    }

    private function imageUrl(Product $product): ?string
    {
        $key = $product->primaryImage()?->storage_key;

        return $key ? Storage::disk('public')->url($key) : null;
    }

    /**
     * Decode the URL-encoded JSON `filters` query parameter into an array.
     *
     * @return array<string, mixed>
     */
    private function parseFilters(?string $filters): array
    {
        if ($filters === null || $filters === '') {
            return [];
        }

        $decoded = json_decode($filters, true);

        if (! is_array($decoded)) {
            abort(400, 'Malformed filters parameter.');
        }

        return $decoded;
    }
}
