<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Store $store */
        $store = app('current_store');
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:255'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'], 'vendor' => ['sometimes', 'string'], 'collection_id' => ['sometimes', 'integer'], 'sort' => ['sometimes', 'in:relevance,newest,price_asc,price_desc']]);

        return ProductResource::collection($this->searchService->search($store, $validated['q'], $request->except(['q', 'per_page']), $validated['per_page'] ?? 24));
    }

    public function suggest(Request $request): AnonymousResourceCollection
    {
        /** @var Store $store */
        $store = app('current_store');
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:255'], 'limit' => ['sometimes', 'integer', 'min:1', 'max:10']]);

        return ProductResource::collection($this->searchService->autocomplete($store, $validated['q'], $validated['limit'] ?? 5));
    }
}
