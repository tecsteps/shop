<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Storefront search API (spec 02 §2.5).
 */
class SearchController extends Controller
{
    public function __construct(private SearchService $search) {}

    /**
     * GET /api/storefront/v1/search — full-text product search.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'filters' => ['nullable', 'string', 'json'],
            'sort' => ['nullable', Rule::in(['relevance', 'price_asc', 'price_desc', 'newest', 'best_selling'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $filters = json_decode($validated['filters'] ?? '[]', true);
        $filters = is_array($filters) ? $filters : [];

        /** @var \App\Models\Store $store */
        $store = app('current_store');

        $paginator = $this->search->search(
            $store,
            $validated['q'],
            $filters,
            (int) ($validated['per_page'] ?? 24),
            $validated['sort'] ?? 'relevance',
            (int) ($validated['page'] ?? 1),
        );

        return response()->json([
            'query' => $validated['q'],
            'results' => collect($paginator->items())
                ->map(fn (Product $product): array => $this->productResult($store, $product))
                ->all(),
            'facets' => $this->search->facets($store, $validated['q']),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/storefront/v1/search/suggest — autocomplete suggestions.
     */
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        return response()->json([
            'query' => $validated['q'],
            'suggestions' => $this->search
                ->autocomplete(app('current_store'), $validated['q'], (int) ($validated['limit'] ?? 5))
                ->values()
                ->all(),
        ]);
    }

    /**
     * Shape a product for the search result payload.
     *
     * @return array<string, mixed>
     */
    private function productResult(\App\Models\Store $store, Product $product): array
    {
        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
        $image = $product->media->firstWhere('status', \App\Enums\MediaStatus::Ready);
        $compareAt = $product->variants->max('compare_at_amount');

        return [
            'id' => $product->id,
            'title' => $product->title,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'price_amount' => $product->variants->min('price_amount'),
            'compare_at_amount' => $compareAt !== null ? (int) $compareAt : null,
            'currency' => $variant?->currency ?? $store->default_currency,
            'image_url' => $image?->url(),
            'in_stock' => $product->variants->contains(
                fn (\App\Models\ProductVariant $variant): bool => $variant->isInStock(),
            ),
            'tags' => $product->tags ?? [],
        ];
    }
}
