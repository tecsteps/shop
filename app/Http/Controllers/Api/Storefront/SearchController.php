<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService) {}

    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:200'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $filters = json_decode((string) $request->input('filters', '[]'), true) ?: [];

        $products = $this->searchService->search(
            app('current_store'),
            $validated['q'],
            $filters,
            $validated['per_page'] ?? 24,
        );

        return response()->json([
            'query' => $validated['q'],
            'results' => $products->map(fn ($product) => [
                'id' => $product->id,
                'title' => $product->title,
                'handle' => $product->handle,
                'vendor' => $product->vendor,
                'product_type' => $product->product_type,
                'price_amount' => $product->variants()->min('price_amount'),
                'tags' => $product->tags,
            ])->values(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function suggest(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $suggestions = $this->searchService->autocomplete(
            app('current_store'),
            $validated['q'],
            $validated['limit'] ?? 5,
        );

        return response()->json([
            'query' => $validated['q'],
            'suggestions' => $suggestions->map(fn ($product) => [
                'type' => 'product',
                'title' => $product->title,
                'handle' => $product->handle,
                'price_amount' => $product->variants()->min('price_amount'),
                'currency' => $product->store?->default_currency,
            ])->values(),
        ]);
    }
}
