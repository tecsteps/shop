<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function orders(Store $store): StreamedResponse
    {
        abort_unless($store->is(app('current_store')), 404);

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['order_number', 'placed_at', 'status', 'financial_status', 'fulfillment_status', 'email', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'currency']);
            Order::query()->latest('placed_at')->lazy()->each(fn (Order $order) => fputcsv($stream, [$order->order_number, $order->placed_at?->toIso8601String(), $order->status->value, $order->financial_status->value, $order->fulfillment_status->value, $order->email, $order->subtotal_amount, $order->discount_amount, $order->shipping_amount, $order->tax_amount, $order->total_amount, $order->currency]));
            fclose($stream);
        }, 'orders-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}
