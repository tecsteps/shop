<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontSearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:200'], 'query' => ['nullable', 'string', 'max:200'], 'vendor' => ['nullable', 'string'], 'min_price' => ['nullable', 'integer', 'min:0'], 'max_price' => ['nullable', 'integer', 'min:0'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);
        $query = $data['q'] ?? $data['query'] ?? '';
        $results = $this->search->search(app('current_store'), $query, array_filter(['vendor' => $data['vendor'] ?? null, 'min_price' => $data['min_price'] ?? null, 'max_price' => $data['max_price'] ?? null], fn ($value): bool => $value !== null), $data['per_page'] ?? 12);

        return response()->json(['data' => collect($results->items())->map(fn ($product): array => ['id' => $product->id, 'title' => $product->title, 'handle' => $product->handle, 'vendor' => $product->vendor, 'price_amount' => $product->defaultVariant()?->price_amount, 'image_url' => $product->media->first()?->url])->values()->all(), 'meta' => ['query' => $query, 'current_page' => $results->currentPage(), 'per_page' => $results->perPage(), 'total' => $results->total(), 'last_page' => $results->lastPage()]]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:80'], 'limit' => ['nullable', 'integer', 'min:1', 'max:10']]);

        return response()->json(['data' => $this->search->autocomplete(app('current_store'), $data['q'], $data['limit'] ?? 8)->map(fn ($product): array => ['id' => $product->id, 'title' => $product->title, 'handle' => $product->handle])->values()->all()]);
    }
}
