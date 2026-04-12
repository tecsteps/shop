<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentDelivered;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FulfillmentService
{
    /**
     * @param  array<int, int>  $lines  Map of order_line_id => quantity.
     * @param  array<string, string|null>|null  $tracking  Optional tracking info keyed by company/number/url.
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        $financialStatus = $order->financial_status instanceof FinancialStatus
            ? $order->financial_status
            : FinancialStatus::from((string) $order->financial_status);

        if (! in_array($financialStatus, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw new FulfillmentGuardException('Cannot fulfill an unpaid order.');
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            /** @var Fulfillment $fulfillment */
            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => FulfillmentShipmentStatus::Pending->value,
                'tracking_company' => $tracking['company'] ?? null,
                'tracking_number' => $tracking['number'] ?? null,
                'tracking_url' => $tracking['url'] ?? null,
            ]);

            foreach ($lines as $orderLineId => $quantity) {
                FulfillmentLine::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            $this->updateOrderFulfillmentStatus($order);

            return $fulfillment;
        });
    }

    /**
     * @param  array<string, string|null>|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        $updates = [
            'status' => FulfillmentShipmentStatus::Shipped->value,
            'shipped_at' => now(),
        ];

        if ($tracking !== null) {
            $updates['tracking_company'] = $tracking['company'] ?? $fulfillment->tracking_company;
            $updates['tracking_number'] = $tracking['number'] ?? $fulfillment->tracking_number;
            $updates['tracking_url'] = $tracking['url'] ?? $fulfillment->tracking_url;
        }

        $fulfillment->update($updates);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->update([
            'status' => FulfillmentShipmentStatus::Delivered->value,
            'delivered_at' => now(),
        ]);

        FulfillmentDelivered::dispatch($fulfillment);
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $order->loadMissing('lines');

        $totalQty = (int) $order->lines->sum('quantity');
        $fulfilledQty = (int) FulfillmentLine::whereIn('order_line_id', $order->lines->pluck('id'))->sum('quantity');

        if ($totalQty > 0 && $fulfilledQty >= $totalQty) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Fulfilled->value,
                'status' => OrderStatus::Fulfilled->value,
            ]);
            OrderFulfilled::dispatch($order);

            return;
        }

        if ($fulfilledQty > 0) {
            $order->update([
                'fulfillment_status' => FulfillmentStatus::Partial->value,
            ]);
        }
    }
}
