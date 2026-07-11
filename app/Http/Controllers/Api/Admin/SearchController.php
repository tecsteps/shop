<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $searchService) {}

    public function reindex(Store $store): JsonResponse
    {
        $this->ensureStore($store);
        DB::table('products_fts')->where('store_id', $store->id)->delete();
        Product::query()->lazyById()->each(fn (Product $product) => $this->searchService->syncProduct($product));

        return response()->json(['indexed' => Product::query()->count()], Response::HTTP_ACCEPTED);
    }

    public function status(Store $store): JsonResponse
    {
        $this->ensureStore($store);

        return response()->json(['data' => ['products' => Product::query()->count(), 'indexed' => DB::table('products_fts')->where('store_id', $store->id)->count()]]);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }
}
