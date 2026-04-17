<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search): JsonResponse
    {
        $store = app('current_store');
        $query = (string) $request->query('q', '');

        $results = $search->search($store, $query, [], $request->session()?->getId());

        return ProductResource::collection($results)
            ->additional(['meta' => ['query' => $query, 'count' => $results->count()]])
            ->response();
    }
}
