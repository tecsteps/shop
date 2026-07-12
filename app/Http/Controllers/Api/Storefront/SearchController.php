<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use JsonException;

final class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'filters' => ['sometimes', 'string', 'max:10000'],
            'sort' => ['sometimes', 'in:relevance,price_asc,price_desc,newest,best_selling'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        try {
            $filters = isset($validated['filters']) ? json_decode($validated['filters'], true, 8, JSON_THROW_ON_ERROR) : [];
        } catch (JsonException) {
            return response()->json(['message' => 'The filters parameter must be valid JSON.'], 400);
        }
        if (! is_array($filters) || ($filters !== [] && array_is_list($filters))) {
            return response()->json(['message' => 'The filters parameter must be a JSON object.'], 400);
        }
        validator($filters, [
            'collection_id' => ['sometimes', 'integer'],
            'price_min' => ['sometimes', 'integer', 'min:0'],
            'price_max' => ['sometimes', 'integer', 'gte:price_min'],
            'in_stock' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:100'],
            'vendor' => ['sometimes', 'string', 'max:255'],
        ])->validate();
        $payload = $this->search->searchWithFacets(app('current_store'), $validated['q'], $filters, $validated['sort'] ?? 'relevance', $validated['per_page'] ?? 24);
        $results = $payload['paginator'];

        return response()->json([
            'query' => $validated['q'],
            'results' => collect($results->items())->map(fn ($product): array => $this->productData($product))->values(),
            'facets' => $payload['facets'],
            'pagination' => ['current_page' => $results->currentPage(), 'per_page' => $results->perPage(), 'total' => $results->total(), 'last_page' => $results->lastPage()],
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:1', 'max:100'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:10']]);
        $suggestions = $this->search->suggestions(app('current_store'), $validated['q'], $validated['limit'] ?? 5)
            ->map(function (array $suggestion): array {
                $model = $suggestion['model'];
                if ($suggestion['type'] === 'collection') {
                    return ['type' => 'collection', 'title' => $model->title, 'handle' => $model->handle, 'image_url' => null];
                }
                $data = $this->productData($model);

                return ['type' => 'product', 'title' => $data['title'], 'handle' => $data['handle'], 'image_url' => $data['image_url'], 'price_amount' => $data['price_amount'], 'currency' => $data['currency']];
            });

        return response()->json(['query' => $validated['q'], 'suggestions' => $suggestions]);
    }

    /** @return array<string, mixed> */
    private function productData($product): array
    {
        $variant = $product->variants->where('status.value', 'active')->sortBy('price_amount')->first()
            ?? $product->variants->sortBy('price_amount')->first();
        $media = $product->media->first();
        $inStock = $product->variants->contains(fn ($item): bool => $item->inventoryItem === null
            || (string) ($item->inventoryItem->policy instanceof \BackedEnum ? $item->inventoryItem->policy->value : $item->inventoryItem->policy) === 'continue'
            || $item->inventoryItem->availableQuantity() > 0);

        return [
            'id' => $product->id,
            'title' => $product->title,
            'handle' => $product->handle,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'price_amount' => (int) ($variant?->price_amount ?? 0),
            'compare_at_amount' => $variant?->compare_at_amount,
            'currency' => $variant?->currency ?? app('current_store')->default_currency,
            'image_url' => $media === null ? null : url(Storage::disk('public')->url($media->storage_key)),
            'in_stock' => $inStock,
            'tags' => (array) $product->tags,
        ];
    }
}
