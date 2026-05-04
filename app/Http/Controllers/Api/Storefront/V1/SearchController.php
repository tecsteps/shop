<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\V1\SearchProductsRequest;
use App\Http\Requests\Api\Storefront\V1\SuggestProductsRequest;
use App\Http\Resources\Storefront\V1\SearchProductResource;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function index(SearchProductsRequest $request, SearchService $search): JsonResponse
    {
        $validated = $request->validated();
        $filters = $validated['filters'] ?? [];
        $query = trim((string) $validated['q']);
        $store = $this->currentStore();

        $results = $search->search(
            $store,
            $query,
            $filters,
            (int) ($validated['per_page'] ?? 24),
            (string) ($validated['sort'] ?? 'relevance'),
        );

        return response()->json([
            'query' => $query,
            'results' => SearchProductResource::collection($results->getCollection())->resolve($request),
            'facets' => $search->facets($store, $query, $filters),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function suggest(SuggestProductsRequest $request, SearchService $search): JsonResponse
    {
        $validated = $request->validated();
        $query = trim((string) $validated['q']);

        return response()->json([
            'query' => $query,
            'suggestions' => $search->suggestions($this->currentStore(), $query, (int) ($validated['limit'] ?? 5)),
        ]);
    }

    private function currentStore(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }
}
