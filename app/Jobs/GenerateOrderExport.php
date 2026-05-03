<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateOrderExport implements ShouldQueue
{
    use Queueable;

    /**
     * @var list<string>
     */
    private const Columns = [
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
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(public int $exportId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $export = Export::withoutGlobalScopes()->findOrFail($this->exportId);

        $export->forceFill([
            'status' => Export::StatusProcessing,
            'failure_message' => null,
            'failed_at' => null,
        ])->save();

        try {
            $orders = $this->orders($export);
            $storageKey = "exports/{$export->store_id}/orders-{$export->id}.csv";

            Storage::disk('local')->put($storageKey, $this->csv($orders));

            $export->forceFill([
                'status' => Export::StatusCompleted,
                'storage_key' => $storageKey,
                'row_count' => $orders->count(),
                'download_expires_at' => now()->addHour(),
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => Export::StatusFailed,
                'failure_message' => $exception->getMessage(),
                'failed_at' => now(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @return Collection<int, Order>
     */
    private function orders(Export $export): Collection
    {
        $filters = $export->filters_json ?? [];

        $query = Order::withoutGlobalScopes()
            ->where('store_id', $export->store_id)
            ->with('customer', 'fulfillments', 'checkout.shippingRate')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['financial_status'] ?? null, fn (Builder $query, string $status) => $query->where('financial_status', $status))
            ->when($filters['fulfillment_status'] ?? null, fn (Builder $query, string $status) => $query->where('fulfillment_status', $status))
            ->when($filters['customer_id'] ?? null, fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['created_after'] ?? null, fn (Builder $query, string $date) => $query->where('placed_at', '>=', $date))
            ->when($filters['created_before'] ?? null, fn (Builder $query, string $date) => $query->where('placed_at', '<=', $date))
            ->when($filters['query'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('order_number', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%')
                        ->orWhereHas('customer', fn (Builder $query) => $query->where('email', 'like', '%'.$term.'%'));
                });
            })
            ->latest('placed_at');

        return $query->get();
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function csv(Collection $orders): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, self::Columns);

        foreach ($orders as $order) {
            fputcsv($handle, [
                $order->order_number,
                $order->placed_at?->toISOString(),
                $order->status->value,
                $order->financial_status->value,
                $order->fulfillment_status->value,
                $order->email,
                $order->customer?->name ?? $this->shippingName($order),
                $order->subtotal_amount,
                $order->discount_amount,
                $order->shipping_amount,
                $order->tax_amount,
                $order->total_amount,
                $order->currency,
                $order->checkout?->shippingRate?->name,
                $order->fulfillments->pluck('tracking_number')->filter()->first(),
            ]);
        }

        rewind($handle);

        return stream_get_contents($handle) ?: '';
    }

    private function shippingName(Order $order): string
    {
        return trim((string) Arr::get($order->shipping_address_json ?? [], 'first_name').' '.(string) Arr::get($order->shipping_address_json ?? [], 'last_name'));
    }
}
