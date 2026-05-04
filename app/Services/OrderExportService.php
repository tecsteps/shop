<?php

namespace App\Services;

use App\Enums\ExportStatus;
use App\Models\DataExport;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class OrderExportService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(Store $store, array $filters): DataExport
    {
        $export = DataExport::query()->create([
            'store_id' => $store->getKey(),
            'type' => 'orders',
            'format' => 'csv',
            'status' => ExportStatus::Queued,
            'filters_json' => $filters,
        ]);

        try {
            $export->forceFill(['status' => ExportStatus::Processing])->save();

            [$csv, $rowCount] = $this->csv($store, $filters);
            $storageKey = "exports/orders/{$export->getKey()}.csv";

            Storage::disk('local')->put($storageKey, $csv);

            $export->forceFill([
                'status' => ExportStatus::Completed,
                'row_count' => $rowCount,
                'storage_key' => $storageKey,
                'download_expires_at' => now()->addHour(),
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => ExportStatus::Failed,
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ])->save();

            throw $exception;
        }

        return $export->refresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: string, 1: int}
     */
    private function csv(Store $store, array $filters): array
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open temporary export stream.');
        }

        fputcsv($handle, [
            'order_number',
            'created_at',
            'status',
            'financial_status',
            'fulfillment_status',
            'customer_email',
            'customer_name',
            'subtotal_amount',
            'discount_amount',
            'shipping_amount',
            'tax_amount',
            'total_amount',
            'currency',
            'shipping_method',
            'tracking_number',
        ]);

        $rowCount = 0;

        $this->query($store, $filters)
            ->with(['customer', 'fulfillments'])
            ->orderBy('id')
            ->each(function (Order $order) use ($handle, &$rowCount): void {
                fputcsv($handle, [
                    $order->order_number,
                    $order->created_at?->toIso8601String(),
                    $order->status?->value,
                    $order->financial_status?->value,
                    $order->fulfillment_status?->value,
                    $order->email,
                    $order->customer?->name,
                    $order->subtotal_amount,
                    $order->discount_amount,
                    $order->shipping_amount,
                    $order->tax_amount,
                    $order->total_amount,
                    $order->currency,
                    '',
                    $order->fulfillments->first()?->tracking_number,
                ]);

                $rowCount++;
            });

        rewind($handle);

        return [(string) stream_get_contents($handle), $rowCount];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Order>
     */
    private function query(Store $store, array $filters): Builder
    {
        return Order::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->when(data_get($filters, 'status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(data_get($filters, 'financial_status'), fn (Builder $query, string $status) => $query->where('financial_status', $status))
            ->when(data_get($filters, 'fulfillment_status'), fn (Builder $query, string $status) => $query->where('fulfillment_status', $status))
            ->when(data_get($filters, 'created_after'), fn (Builder $query, string $date) => $query->where('created_at', '>=', $date))
            ->when(data_get($filters, 'created_before'), fn (Builder $query, string $date) => $query->where('created_at', '<=', $date))
            ->when(data_get($filters, 'query'), function (Builder $query, string $search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('order_number', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('customer', fn (Builder $query) => $query->where('name', 'like', $like));
                });
            });
    }
}
