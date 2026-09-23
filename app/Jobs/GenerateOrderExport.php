<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderExport;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateOrderExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $exportId) {}

    public function handle(): void
    {
        $export = OrderExport::query()->findOrFail($this->exportId);
        $export->update(['status' => 'processing', 'error_message' => null]);
        $path = 'order-exports/'.$export->store_id.'/'.$export->getKey().'.csv';
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new \RuntimeException('Could not open a temporary stream for the order export.');
        }

        try {
            fputcsv($stream, [
                'order_number', 'status', 'financial_status', 'fulfillment_status', 'customer_email',
                'currency', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount',
                'total_amount', 'placed_at', 'created_at',
            ], ',', '"', '');
            $rowCount = 0;
            $query = Order::withoutGlobalScopes()
                ->where('store_id', $export->store_id)
                ->with(['customer' => fn ($customers) => $customers->where('store_id', $export->store_id)]);
            $this->applyFilters($query, $export->filters_json ?? [], (int) $export->store_id);

            $query->orderBy('id')->chunkById(500, function ($orders) use ($stream, &$rowCount): void {
                foreach ($orders as $order) {
                    fputcsv($stream, [
                        $order->order_number,
                        $order->status,
                        $order->financial_status,
                        $order->fulfillment_status,
                        $order->customer?->email ?? $order->email,
                        $order->currency,
                        $order->subtotal_amount,
                        $order->discount_amount,
                        $order->shipping_amount,
                        $order->tax_amount,
                        $order->total_amount,
                        $order->placed_at,
                        $order->created_at,
                    ], ',', '"', '');
                    $rowCount++;
                }
            });

            rewind($stream);

            if (! Storage::disk('local')->put($path, $stream)) {
                throw new \RuntimeException('The order export could not be written to storage.');
            }

            $export->update([
                'status' => 'completed',
                'row_count' => $rowCount,
                'storage_key' => $path,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } finally {
            fclose($stream);
        }
    }

    public function failed(?Throwable $exception): void
    {
        OrderExport::query()->whereKey($this->exportId)->update([
            'status' => 'failed',
            'error_message' => $exception === null ? null : mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters, int $storeId): void
    {
        foreach (['status', 'financial_status', 'fulfillment_status', 'customer_id'] as $filter) {
            if (isset($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (isset($filters['created_after'])) {
            $query->where('placed_at', '>=', CarbonImmutable::parse($filters['created_after'])->toDateTimeString());
        }

        if (isset($filters['created_before'])) {
            $query->where('placed_at', '<=', CarbonImmutable::parse($filters['created_before'])->toDateTimeString());
        }

        if (isset($filters['query'])) {
            $text = $filters['query'];
            $query->where(function ($builder) use ($text, $storeId): void {
                $builder->where('order_number', 'like', "%{$text}%")
                    ->orWhere('email', 'like', "%{$text}%")
                    ->orWhereHas('customer', fn ($customers) => $customers
                        ->withoutGlobalScopes()
                        ->where('store_id', $storeId)
                        ->where('email', 'like', "%{$text}%"));
            });
        }
    }
}
