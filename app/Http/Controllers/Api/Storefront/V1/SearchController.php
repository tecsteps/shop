<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Storefront\SearchRequest;
use App\Http\Requests\Api\Storefront\SuggestRequest;
use App\Services\SearchService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $search,
        protected CurrentStore $currentStore,
    ) {}

    public function index(SearchRequest $request): JsonResponse
    {
        $store = $this->currentStore->get();

        abort_if($store === null, 404);

        $validated = $request->validated();
        $filters = $request->parsedFilters();

        if (isset($filters['__invalid'])) {
            return response()->json(['message' => 'The filters are malformed.'], 422);
        }

        $perPage = $validated['per_page'] ?? 24;
        $page = $validated['page'] ?? 1;

        $result = $this->search->search(
            $store,
            $validated['q'],
            $filters,
            $validated['sort'] ?? 'relevance',
            $perPage,
            $page,
        );

        $paginator = $result['results'];

        return response()->json([
            'query' => $validated['q'],
            'results' => $paginator->items(),
            'facets' => $result['facets'],
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function suggest(SuggestRequest $request): JsonResponse
    {
        $store = $this->currentStore->get();

        abort_if($store === null, 404);

        $validated = $request->validated();

        $suggestions = $this->search->autocomplete($store, $validated['q'], $validated['limit'] ?? 5);

        return response()->json([
            'query' => $validated['q'],
            'suggestions' => $suggestions->all(),
        ]);
    }
}
