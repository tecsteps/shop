<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:1', 'max:200'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']]);
        $results = $this->search->search(app('current_store'), $validated['q'], $request->only(['vendor', 'collection_id']), $validated['per_page'] ?? 12);

        return response()->json(['data' => $results->items(), 'meta' => ['current_page' => $results->currentPage(), 'last_page' => $results->lastPage(), 'total' => $results->total()]]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        return response()->json(['data' => $this->search->autocomplete(app('current_store'), $validated['q'])]);
    }
}
