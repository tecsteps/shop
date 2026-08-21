<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontOrderController extends Controller
{
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::query()->with(['lines', 'payments', 'fulfillments.lines'])->where('order_number', $orderNumber)->firstOrFail();
        $customerId = $request->user('customer')?->getKey();
        $token = (string) $request->query('token', '');
        $expected = hash_hmac('sha256', $order->order_number, (string) config('app.key'));
        abort_unless(($customerId !== null && (int) $order->customer_id === (int) $customerId) || ($token !== '' && hash_equals($expected, $token)), 404);

        return response()->json(['data' => ['id' => $order->id, 'order_number' => $order->order_number, 'status' => $order->status, 'financial_status' => $order->financial_status, 'fulfillment_status' => $order->fulfillment_status, 'currency' => $order->currency, 'email' => $order->email, 'subtotal_amount' => $order->subtotal_amount, 'discount_amount' => $order->discount_amount, 'shipping_amount' => $order->shipping_amount, 'tax_amount' => $order->tax_amount, 'total_amount' => $order->total_amount, 'shipping_address' => $order->shipping_address_json, 'lines' => $order->lines->map(fn ($line): array => ['id' => $line->id, 'title' => $line->title_snapshot, 'quantity' => $line->quantity, 'unit_price_amount' => $line->unit_price_amount, 'total_amount' => $line->line_total_amount])->all(), 'payments' => $order->payments->map(fn ($payment): array => ['method' => $payment->method, 'status' => $payment->status, 'amount' => $payment->amount])->all(), 'fulfillments' => $order->fulfillments->map(fn ($fulfillment): array => ['id' => $fulfillment->id, 'status' => $fulfillment->status, 'tracking_number' => $fulfillment->tracking_number])->all()]]);
    }
}
