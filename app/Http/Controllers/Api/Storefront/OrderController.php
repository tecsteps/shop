<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Guest order status endpoint (spec 02 §2.4). Access is controlled via an
 * HMAC-signed token (see OrderToken) handed out on the confirmation page
 * and in confirmation emails.
 */
class OrderController extends Controller
{
    /**
     * GET /api/storefront/v1/orders/{orderNumber}?token=...
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::with(['lines.variant', 'fulfillments'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        abort_unless(OrderToken::validate($order, $request->query('token')), 401, 'Invalid or missing order token.');

        $address = $order->shipping_address_json ?? [];

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'financial_status' => $order->financial_status->value,
            'fulfillment_status' => $order->fulfillment_status->value,
            'email' => $order->email,
            'currency' => $order->currency,
            'placed_at' => $order->placed_at?->toIso8601ZuluString(),
            'lines' => $order->lines->map(fn ($line): array => [
                'title_snapshot' => $line->title_snapshot,
                'variant_title' => $line->variant?->title(),
                'sku_snapshot' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'total_amount' => $line->total_amount,
            ])->all(),
            'totals' => [
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
            ],
            'shipping_address' => [
                'first_name' => $address['first_name'] ?? null,
                'last_name' => $address['last_name'] ?? null,
                'address1' => $address['address1'] ?? null,
                'city' => $address['city'] ?? null,
                'country' => $address['country_code'] ?? $address['country'] ?? null,
                'postal_code' => $address['postal_code'] ?? null,
            ],
            'fulfillments' => $order->fulfillments->map(fn ($fulfillment): array => [
                'id' => $fulfillment->id,
                'status' => $fulfillment->status->value,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toIso8601ZuluString(),
            ])->all(),
        ]);
    }
}
