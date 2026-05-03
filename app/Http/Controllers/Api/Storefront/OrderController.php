<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(Request $request, string $orderNumber, OrderService $orders): JsonResponse
    {
        $order = Order::withoutGlobalScopes()
            ->with('lines.variant.optionValues.option', 'payments', 'fulfillments.lines')
            ->where('store_id', app('current_store')->id)
            ->where('order_number', $this->normalizeOrderNumber($orderNumber))
            ->firstOrFail();

        $token = (string) $request->query('token', '');

        if ($token === '' || ! $orders->validAccessToken($order, $token)) {
            abort(401, 'Invalid order access token.');
        }

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'financial_status' => $order->financial_status->value,
            'fulfillment_status' => $order->fulfillment_status->value,
            'email' => $order->email,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toISOString(),
            'lines' => $order->lines->map(fn ($line): array => [
                'title_snapshot' => $line->title_snapshot,
                'variant_title' => $line->variant?->optionValues?->pluck('value')->join(' / ') ?: null,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
            ])->values()->all(),
            'totals' => [
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
            ],
            'shipping_address' => $order->shipping_address_json ?? [],
            'fulfillments' => $order->fulfillments->map(fn ($fulfillment): array => [
                'id' => $fulfillment->id,
                'status' => $fulfillment->status->value,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toISOString(),
                'delivered_at' => $fulfillment->delivered_at?->toISOString(),
            ])->values()->all(),
        ]);
    }

    private function normalizeOrderNumber(string $orderNumber): string
    {
        return str_starts_with($orderNumber, '#') ? $orderNumber : '#'.$orderNumber;
    }
}
