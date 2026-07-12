<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::withoutGlobalScopes()->where('store_id', app('current_store')->id)->where('order_number', $orderNumber)->with(['lines', 'fulfillments'])->firstOrFail();
        $expected = hash_hmac('sha256', $order->order_number.'|'.$order->email, (string) config('app.key'));
        abort_unless(is_string($request->query('token')) && hash_equals($expected, $request->query('token')), 401);

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
