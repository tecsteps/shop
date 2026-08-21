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
        abort_unless(($customerId !== null && (int) $order->customer_id === (int) $customerId) || ($token !== '' && hash_equals($expected, $token)), 401, 'A valid order access token is required.');

        return response()->json(['order_number' => $order->order_number, 'status' => $order->status, 'financial_status' => $order->financial_status, 'fulfillment_status' => $order->fulfillment_status, 'email' => $order->email, 'currency' => $order->currency, 'placed_at' => $order->placed_at, 'lines' => $order->lines->map(fn ($line): array => ['title_snapshot' => $line->title_snapshot, 'variant_title' => $line->variant_title, 'sku_snapshot' => $line->sku_snapshot, 'quantity' => $line->quantity, 'unit_price_amount' => $line->unit_price_amount, 'total_amount' => $line->line_total_amount])->all(), 'totals' => ['subtotal_amount' => $order->subtotal_amount, 'discount_amount' => $order->discount_amount, 'shipping_amount' => $order->shipping_amount, 'tax_amount' => $order->tax_amount, 'total_amount' => $order->total_amount], 'shipping_address' => $order->shipping_address_json, 'billing_address' => $order->billing_address_json, 'fulfillments' => $order->fulfillments->map(fn ($fulfillment): array => ['id' => $fulfillment->id, 'status' => $fulfillment->status, 'tracking_company' => $fulfillment->tracking_company, 'tracking_number' => $fulfillment->tracking_number, 'tracking_url' => $fulfillment->tracking_url])->all()]);
    }
}
