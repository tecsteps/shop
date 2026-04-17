<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Orders\ShowOrderRequest;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ResolvesApiContext;

    public function show(ShowOrderRequest $request, string $orderNumber): JsonResponse
    {
        $storeId = $this->currentStoreId($request);
        $validated = $request->validated();

        $order = Order::query()
            ->where('store_id', $storeId)
            ->where('order_number', $orderNumber)
            ->with([
                'lines',
                'fulfillments',
            ])
            ->first();

        if (! $order instanceof Order) {
            return response()->json([
                'message' => 'Order not found.',
            ], 404);
        }

        $providedToken = (string) ($validated['token'] ?? '');
        $expectedToken = $this->expectedOrderToken($order);

        if (! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'token' => ['The provided order token is invalid.'],
                ],
            ], 422);
        }

        $lines = $order->lines->map(function (OrderLine $line): array {
            return [
                'title_snapshot' => (string) $line->title_snapshot,
                'variant_title' => null,
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => (int) $line->quantity,
                'unit_price_amount' => (int) $line->unit_price_amount,
                'total_amount' => (int) $line->total_amount,
            ];
        })->values()->all();

        $fulfillments = $order->fulfillments->map(function ($fulfillment): array {
            return [
                'id' => (int) $fulfillment->id,
                'status' => (string) $this->enumValue($fulfillment->status),
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $this->iso($fulfillment->shipped_at),
                'created_at' => $this->iso($fulfillment->created_at),
            ];
        })->values()->all();

        return response()->json([
            'order_number' => (string) $order->order_number,
            'status' => (string) $this->enumValue($order->status),
            'financial_status' => (string) $this->enumValue($order->financial_status),
            'fulfillment_status' => (string) $this->enumValue($order->fulfillment_status),
            'email' => $order->email,
            'currency' => (string) $order->currency,
            'placed_at' => $this->iso($order->placed_at),
            'lines' => $lines,
            'totals' => [
                'subtotal_amount' => (int) $order->subtotal_amount,
                'discount_amount' => (int) $order->discount_amount,
                'shipping_amount' => (int) $order->shipping_amount,
                'tax_amount' => (int) $order->tax_amount,
                'total_amount' => (int) $order->total_amount,
            ],
            'shipping_address' => is_array($order->shipping_address_json) ? $order->shipping_address_json : null,
            'fulfillments' => $fulfillments,
        ]);
    }

    private function expectedOrderToken(Order $order): string
    {
        $key = (string) config('app.key', '');

        if (Str::startsWith($key, 'base64:')) {
            $decoded = base64_decode(Str::after($key, 'base64:'), true);

            if ($decoded !== false) {
                $key = $decoded;
            }
        }

        return hash_hmac('sha256', implode('|', [
            (string) $order->store_id,
            (string) $order->order_number,
            (string) ($order->email ?? ''),
        ]), $key);
    }
}
