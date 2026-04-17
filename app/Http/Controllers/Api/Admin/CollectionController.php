<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Models\Collection as CollectionModel;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request, int $storeId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $collections = CollectionModel::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return CollectionResource::collection($collections)->response();
    }

    public function show(Request $request, int $storeId, int $collectionId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $collection = CollectionModel::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->findOrFail($collectionId);

        return (new CollectionResource($collection))->response();
    }

    protected function resolveStore(Request $request, int $storeId): Store
    {
        $user = $request->user();
        $store = Store::query()->findOrFail($storeId);

        if ($user === null || ! $user->stores()->wherePivot('store_id', $store->getKey())->exists()) {
            abort(403);
        }

        app()->instance('current_store', $store);

        return $store;
    }
}
