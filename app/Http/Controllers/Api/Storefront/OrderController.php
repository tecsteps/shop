<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;

class OrderController extends Controller
{
    public function show(string $orderNumber): OrderResource
    {
        $order = Order::query()->where('order_number', $orderNumber)->with(['lines', 'payments', 'refunds', 'fulfillments.lines'])->firstOrFail();

        return new OrderResource($order);
    }
}
