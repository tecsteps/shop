<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function show(string $orderNumber): JsonResponse
    {
        $store = app('current_store');

        $order = Order::query()
            ->where('store_id', $store->getKey())
            ->where('order_number', $orderNumber)
            ->with('lines')
            ->firstOrFail();

        return (new OrderResource($order))->response();
    }
}
