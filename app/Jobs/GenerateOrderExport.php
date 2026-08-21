<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateOrderExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public OrderExport $export) {}

    public function handle(): void
    {
        $this->export->update(['status' => 'processing', 'error_message' => null]);

        try {
            $filters = $this->export->filters_json ?? [];
            $orders = Order::withoutGlobalScopes()
                ->where('store_id', $this->export->store_id)
                ->with(['customer', 'checkout.shippingRate'])
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['financial_status'] ?? null, fn ($query, string $status) => $query->where('financial_status', $status))
                ->when($filters['created_after'] ?? null, fn ($query, string $date) => $query->where('created_at', '>=', $date))
                ->when($filters['created_before'] ?? null, fn ($query, string $date) => $query->where('created_at', '<=', $date))
                ->orderBy('created_at')
                ->get();

            $handle = fopen('php://temp', 'w+');
            fputcsv($handle, ['order_number', 'created_at', 'status', 'financial_status', 'fulfillment_status', 'customer_email', 'customer_name', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'currency', 'shipping_method', 'tracking_number']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->created_at?->toIso8601String(),
                    $order->status?->value ?? $order->status,
                    $order->financial_status?->value ?? $order->financial_status,
                    $order->fulfillment_status?->value ?? $order->fulfillment_status,
                    $order->customer?->email ?? $order->email,
                    $order->customer?->name,
                    $order->subtotal_amount,
                    $order->discount_amount,
                    $order->shipping_amount,
                    $order->tax_amount,
                    $order->total_amount,
                    $order->currency,
                    $order->checkout?->shippingRate?->name,
                    $order->fulfillments()->latest()->value('tracking_number'),
                ]);
            }

            rewind($handle);
            $contents = stream_get_contents($handle);
            fclose($handle);
            $key = 'exports/orders-'.$this->export->getKey().'-'.now()->format('YmdHis').'.csv';
            Storage::disk('public')->put($key, $contents);
            $expiresAt = now()->addHour();

            $this->export->update(['status' => 'completed', 'row_count' => $orders->count(), 'storage_key' => $key, 'download_url' => Storage::disk('public')->url($key), 'download_expires_at' => $expiresAt, 'completed_at' => now()]);
        } catch (Throwable $exception) {
            $this->export->update(['status' => 'failed', 'error_message' => $exception->getMessage()]);

            throw $exception;
        }
    }
}
