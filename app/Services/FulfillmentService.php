<?php

namespace App\Services;

use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfillmentService
{
    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $lines
     * @param  array{tracking_company?: string|null, tracking_number?: string|null, tracking_url?: string|null}  $tracking
     */
    public function create(Order $order, array $lines, array $tracking = []): Fulfillment
    {
        if (! $order->financial_status->allowsFulfillment()) {
            throw new FulfillmentGuardException(
                'Fulfillment cannot be created until payment is confirmed.',
            );
        }

        if (empty($lines)) {
            throw new RuntimeException('At least one fulfillment line is required.');
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            foreach ($lines as $entry) {
                $orderLine = OrderLine::query()->where('order_id', $order->getKey())
                    ->where('id', $entry['order_line_id'])
                    ->firstOrFail();

                $unfulfilled = $orderLine->unfulfilledQuantity();

                if ((int) $entry['quantity'] > $unfulfilled) {
                    throw new RuntimeException("Requested quantity exceeds unfulfilled on line {$orderLine->getKey()}.");
                }
            }

            $fulfillment = Fulfillment::query()->create([
                'order_id' => $order->getKey(),
                'status' => FulfillmentShipmentStatus::Pending->value,
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
                'created_at' => now(),
            ]);

            foreach ($lines as $entry) {
                FulfillmentLine::query()->create([
                    'fulfillment_id' => $fulfillment->getKey(),
                    'order_line_id' => (int) $entry['order_line_id'],
                    'quantity' => (int) $entry['quantity'],
                ]);
            }

            $this->syncOrderFulfillmentStatus($order);

            FulfillmentCreated::dispatch($fulfillment);

            return $fulfillment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, array $tracking = []): Fulfillment
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Pending) {
            throw new RuntimeException('Only pending fulfillments can be marked shipped.');
        }

        $fulfillment->fill([
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
        ]);
        $fulfillment->status = FulfillmentShipmentStatus::Shipped;
        $fulfillment->shipped_at = now();
        $fulfillment->save();

        FulfillmentShipped::dispatch($fulfillment);

        return $fulfillment->refresh();
    }

    public function markAsDelivered(Fulfillment $fulfillment): Fulfillment
    {
        if ($fulfillment->status !== FulfillmentShipmentStatus::Shipped) {
            throw new RuntimeException('Only shipped fulfillments can be marked delivered.');
        }

        $fulfillment->status = FulfillmentShipmentStatus::Delivered;
        $fulfillment->save();

        FulfillmentDelivered::dispatch($fulfillment);

        return $fulfillment->refresh();
    }

    protected function syncOrderFulfillmentStatus(Order $order): void
    {
        $lines = $order->lines()->get();
        $allFulfilled = true;
        $anyFulfilled = false;

        foreach ($lines as $line) {
            $fulfilled = $line->fulfilledQuantity();

            if ($fulfilled > 0) {
                $anyFulfilled = true;
            }

            if ($fulfilled < $line->quantity) {
                $allFulfilled = false;
            }
        }

        if ($allFulfilled) {
            $order->fulfillment_status = FulfillmentStatus::Fulfilled;
            $order->status = OrderStatus::Fulfilled;
            $order->save();

            OrderFulfilled::dispatch($order);
        } elseif ($anyFulfilled) {
            $order->fulfillment_status = FulfillmentStatus::Partial;
            $order->save();
        }
    }
}
