<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SearchRequest;
use App\Http\Requests\Storefront\SearchSuggestRequest;
use App\Http\Resources\Storefront\ProductSearchResource;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SearchController extends Controller
{
    public function index(SearchRequest $request, SearchService $search): JsonResponse
    {
        $store = $this->currentStore();
        $query = trim((string) $request->validated('q', ''));
        $filters = $this->filters($request);
        $sort = (string) $request->validated('sort', 'relevance');
        $perPage = (int) $request->validated('per_page', 24);
        $products = $search->search($store, $query, $filters, $perPage, $sort);

        return response()->json([
            'query' => $query,
            'results' => ProductSearchResource::collection($products->getCollection())->resolve(),
            'facets' => $search->facets($store, $query),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function suggest(SearchSuggestRequest $request, SearchService $search): JsonResponse
    {
        $query = trim((string) $request->validated('q'));

        return response()->json([
            'query' => $query,
            'suggestions' => $search->autocomplete(
                $this->currentStore(),
                $query,
                (int) $request->validated('limit', 5),
            ),
        ]);
    }

    private function currentStore(): Store
    {
        return app('current_store');
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(SearchRequest $request): array
    {
        $filters = $request->validated('filters', []);

        if (is_string($filters) && trim($filters) !== '') {
            $decoded = json_decode($filters, true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'filters' => ['The filters field must be valid JSON.'],
                ]);
            }

            $filters = $decoded;
        }

        if (! is_array($filters)) {
            $filters = [];
        }

        foreach (['vendor', 'product_type', 'collection_id', 'price_min', 'price_max', 'in_stock', 'tags'] as $key) {
            if ($request->filled($key)) {
                $filters[$key] = $request->validated($key);
            }
        }

        return $filters;
    }
}
