<?php

namespace App\Jobs;

use App\Models\Checkout;
use App\Models\Order;
use App\Models\OrderExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class GenerateOrderExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly OrderExport $export) {}

    public function handle(): void
    {
        $export = OrderExport::withoutGlobalScopes()->find($this->export->id);
        if ($export === null) {
            return;
        }

        $export->update(['status' => 'processing', 'error_message' => null]);

        try {
            $filters = (array) $export->filters_json;
            $orders = Order::withoutGlobalScopes()
                ->where('store_id', $export->store_id)
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['financial_status'] ?? null, fn ($query, $status) => $query->where('financial_status', $status))
                ->when($filters['created_after'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', $date))
                ->when($filters['created_before'] ?? null, fn ($query, $date) => $query->where('created_at', '<=', $date))
                ->with(['customer', 'fulfillments'])
                ->orderBy('id')
                ->get();
            $shippingMethods = Checkout::withoutGlobalScopes()
                ->where('store_id', $export->store_id)
                ->where('status', 'completed')
                ->with('shippingRate')
                ->get()
                ->mapWithKeys(fn (Checkout $checkout): array => [(int) data_get($checkout->totals_json, 'order_id') => $checkout->shippingRate?->name]);

            $stream = fopen('php://temp', 'w+b');
            if ($stream === false) {
                throw new \RuntimeException('Could not create the export stream.');
            }
            fputcsv($stream, ['order_number', 'created_at', 'status', 'financial_status', 'fulfillment_status', 'customer_email', 'customer_name', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'total_amount', 'currency', 'shipping_method', 'tracking_number']);
            foreach ($orders as $order) {
                fputcsv($stream, [
                    $order->order_number,
                    $order->created_at?->toIso8601String(),
                    $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
                    $order->financial_status instanceof \BackedEnum ? $order->financial_status->value : $order->financial_status,
                    $order->fulfillment_status instanceof \BackedEnum ? $order->fulfillment_status->value : $order->fulfillment_status,
                    $order->email,
                    $order->customer?->name ?: trim((string) data_get($order->shipping_address_json, 'first_name').' '.(string) data_get($order->shipping_address_json, 'last_name')),
                    $order->subtotal_amount,
                    $order->discount_amount,
                    $order->shipping_amount,
                    $order->tax_amount,
                    $order->total_amount,
                    $order->currency,
                    $shippingMethods->get($order->id),
                    $order->fulfillments->first()?->tracking_number,
                ]);
            }
            rewind($stream);
            $contents = stream_get_contents($stream);
            fclose($stream);
            if ($contents === false) {
                throw new \RuntimeException('Could not read the export stream.');
            }

            $key = "exports/{$export->store_id}/orders-{$export->id}.csv";
            Storage::disk('local')->put($key, $contents);
            $export->update([
                'status' => 'completed',
                'row_count' => $orders->count(),
                'storage_key' => $key,
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $export->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 2000)]);
            throw $exception;
        }
    }
}
