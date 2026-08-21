<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class FulfillmentService
{
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            throw new FulfillmentGuardException;
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $order->load(['lines', 'fulfillments.lines']);
            $fulfilledQuantities = $order->fulfillments->flatMap->lines->groupBy('order_line_id')->map(fn ($items): int => (int) $items->sum('quantity'));
            $requestedQuantities = [];

            foreach ($lines as $line) {
                $orderLine = $order->lines->firstWhere('id', $line['order_line_id']);
                $quantity = (int) ($line['quantity'] ?? 0);
                $requestedQuantities[$line['order_line_id']] = ($requestedQuantities[$line['order_line_id']] ?? 0) + $quantity;

                if ($orderLine === null || $quantity < 1 || $orderLine->quantity < $requestedQuantities[$line['order_line_id']] + (int) ($fulfilledQuantities[$orderLine->id] ?? 0)) {
                    throw new \InvalidArgumentException('The fulfillment quantity exceeds the unfulfilled quantity.');
                }
            }

            $fulfillment = $order->fulfillments()->create(array_merge($tracking ?? [], ['status' => 'pending']));

            foreach ($lines as $line) {
                $fulfillment->lines()->create(['order_line_id' => $line['order_line_id'], 'quantity' => $line['quantity']]);
            }

            $this->refreshOrderStatus($order->refresh());
            FulfillmentCreated::dispatch($fulfillment->refresh());

            return $fulfillment->load('lines.orderLine');
        });
    }

    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        if ($fulfillment->status !== 'pending') {
            throw new \LogicException('Only pending fulfillments can be shipped.');
        }

        $fulfillment->update(array_merge($tracking ?? [], ['status' => 'shipped', 'shipped_at' => now(), 'fulfilled_at' => now()]));
        FulfillmentShipped::dispatch($fulfillment->refresh());
        $this->refreshOrderStatus($fulfillment->load('order')->order);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        if ($fulfillment->status !== 'shipped') {
            throw new \LogicException('Only shipped fulfillments can be delivered.');
        }

        $fulfillment->update(['status' => 'delivered', 'delivered_at' => now()]);
        $fulfillment->load('order');
        FulfillmentDelivered::dispatch($fulfillment);

        if ($fulfillment->order !== null) {
            $this->refreshOrderStatus($fulfillment->order);
        }
    }

    private function refreshOrderStatus(Order $order): void
    {
        $order->load(['lines', 'fulfillments.lines']);
        $fulfilledQuantities = [];

        foreach ($order->fulfillments->whereIn('status', ['shipped', 'delivered']) as $fulfillment) {
            foreach ($fulfillment->lines as $line) {
                $fulfilledQuantities[$line->order_line_id] = ($fulfilledQuantities[$line->order_line_id] ?? 0) + $line->quantity;
            }
        }

        $fulfilled = collect($order->lines)->every(fn ($line): bool => ($fulfilledQuantities[$line->id] ?? 0) >= $line->quantity);
        $partial = $fulfilledQuantities !== [];
        $order->update(['fulfillment_status' => $fulfilled ? FulfillmentStatus::Fulfilled : ($partial ? FulfillmentStatus::Partial : FulfillmentStatus::Unfulfilled)]);

        if ($fulfilled) {
            $order->update(['status' => OrderStatus::Fulfilled]);
            OrderFulfilled::dispatch($order->refresh());
        } elseif ($order->status === OrderStatus::Fulfilled) {
            $order->update(['status' => OrderStatus::Paid]);
        }
    }
}
