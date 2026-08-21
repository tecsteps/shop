<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontSearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'filters' => ['nullable', 'json'],
            'sort' => ['nullable', 'in:relevance,price_asc,price_desc,newest,best_selling'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $filters = ($data['filters'] ?? null) === null ? [] : json_decode($data['filters'], true, 512, JSON_THROW_ON_ERROR);
        abort_unless(is_array($filters) && ($filters === [] || ! array_is_list($filters)), 422, 'The filters parameter must be a JSON object.');
        $results = $this->search->search(app('current_store'), $data['q'], $filters, $data['per_page'] ?? 24, $data['page'] ?? 1, $data['sort'] ?? 'relevance');
        $items = collect($results->items());
        $prices = $items->map(fn ($product): int => (int) ($product->defaultVariant()?->price_amount ?? 0))->filter();
        $vendors = $items->pluck('vendor')->filter()->countBy()->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])->values();
        $tags = $items->flatMap(fn ($product): array => $product->tags ?? [])->countBy()->map(fn (int $count, string $value): array => ['value' => $value, 'count' => $count])->values();

        return response()->json([
            'query' => $data['q'],
            'results' => $items->map(fn ($product): array => [
                'id' => $product->id,
                'title' => $product->title,
                'handle' => $product->handle,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'price_amount' => $product->defaultVariant()?->price_amount,
                'compare_at_amount' => $product->defaultVariant()?->compare_at_amount,
                'currency' => $product->defaultVariant()?->currency,
                'image_url' => $product->media->first()?->url,
                'in_stock' => $product->variants->contains(fn ($variant): bool => $variant->availableQuantity() > 0 || $variant->inventory?->policy?->value === 'continue'),
                'tags' => $product->tags ?? [],
            ])->values()->all(),
            'facets' => ['vendors' => $vendors, 'tags' => $tags, 'price_range' => ['min' => $prices->min(), 'max' => $prices->max()]],
            'pagination' => ['current_page' => $results->currentPage(), 'per_page' => $results->perPage(), 'total' => $results->total(), 'last_page' => $results->lastPage()],
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:1', 'max:100'], 'limit' => ['nullable', 'integer', 'min:1', 'max:10']]);
        $store = app('current_store');
        $limit = $data['limit'] ?? 5;
        $products = $this->search->autocomplete($store, $data['q'], $limit);
        $remaining = max(0, $limit - $products->count());
        $collections = $remaining === 0 ? collect() : Collection::query()->where('status', 'active')->where('title', 'like', trim($data['q']).'%')->with('products.media')->orderBy('title')->limit($remaining)->get();

        return response()->json(['query' => $data['q'], 'suggestions' => [
            ...$products->map(fn ($product): array => ['type' => 'product', 'title' => $product->title, 'handle' => $product->handle, 'image_url' => $product->media->first()?->url, 'price_amount' => $product->defaultVariant()?->price_amount, 'currency' => $product->defaultVariant()?->currency])->all(),
            ...$collections->map(fn (Collection $collection): array => ['type' => 'collection', 'title' => $collection->title, 'handle' => $collection->handle, 'image_url' => $collection->image_url])->all(),
        ]]);
    }
}
