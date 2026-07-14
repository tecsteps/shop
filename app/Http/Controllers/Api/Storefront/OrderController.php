<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderStatusLink $links) {}

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::withoutGlobalScopes()->where('store_id', app('current_store')->id)->where('order_number', $orderNumber)->with(['lines', 'fulfillments'])->firstOrFail();
        abort_unless($this->links->verify($order, $request->query('token')), 401);

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'email' => $order->email,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at,
            'lines' => $order->lines,
            'totals' => [
                'subtotal_amount' => $order->subtotal_amount, 'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount, 'tax_amount' => $order->tax_amount, 'total_amount' => $order->total_amount,
            ],
            'shipping_address' => $order->shipping_address_json,
            'fulfillments' => $order->fulfillments,
        ]);
    }
}
