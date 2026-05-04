<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\V1\OrderResource;
use App\Models\Order;
use App\Models\Store;
use App\Support\OrderAccessToken;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, string $orderNumber): OrderResource
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $order = Order::withoutGlobalScopes()
            ->with(['lines', 'payments', 'fulfillments.lines'])
            ->where('store_id', $store->getKey())
            ->where('order_number', urldecode($orderNumber))
            ->firstOrFail();

        abort_unless(OrderAccessToken::valid($order, $request->query('token')), 404);

        return OrderResource::make($order);
    }
}
