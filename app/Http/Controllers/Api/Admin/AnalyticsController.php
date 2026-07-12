<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function summary(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from'], 'granularity' => ['sometimes', 'in:day,week,month']]);
        $daily = $this->analytics->getDailyMetrics(app('current_store'), $data['from'], $data['to']);

        return response()->json(['data' => [
            'revenue_amount' => (int) $daily->sum('revenue_amount'),
            'orders_count' => (int) $daily->sum('orders_count'),
            'visits_count' => (int) $daily->sum('visits_count'),
            'series' => $daily,
        ]]);
    }

    public function exportOrders(int $storeId): StreamedResponse
    {
        return response()->streamDownload(function () use ($storeId): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['order_number', 'created_at', 'status', 'financial_status', 'fulfillment_status', 'customer_email', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'currency', 'tracking_number']);
            Order::withoutGlobalScopes()->where('store_id', $storeId)->with('fulfillments')->orderBy('id')->each(function (Order $order) use ($out): void {
                fputcsv($out, [$order->order_number, $order->created_at?->toIso8601String(), $order->status, $order->financial_status, $order->fulfillment_status, $order->email, $order->subtotal_amount, $order->discount_amount, $order->shipping_amount, $order->tax_amount, $order->total_amount, $order->currency, $order->fulfillments->first()?->tracking_number]);
            });
            fclose($out);
        }, 'orders-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}
