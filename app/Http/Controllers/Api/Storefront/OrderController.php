<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'email' => $order->email,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toISOString(),
            'lines' => $order->lines->map(fn ($line) => [
                'title_snapshot' => $line->title_snapshot,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
            ]),
            'totals' => [
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
            ],
            'fulfillments' => $order->fulfillments->map(fn ($f) => [
                'status' => $f->status,
                'tracking_number' => $f->tracking_number,
            ]),
        ]);
    }
}
