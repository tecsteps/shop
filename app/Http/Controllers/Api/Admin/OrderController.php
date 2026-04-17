<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, int $storeId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $orders = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->orderByDesc('placed_at')
            ->limit(100)
            ->get();

        return OrderResource::collection($orders)->response();
    }

    public function show(Request $request, int $storeId, int $orderId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $order = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->with('lines')
            ->findOrFail($orderId);

        return (new OrderResource($order))->response();
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
