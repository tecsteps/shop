<?php

namespace App\Services;

use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FulfillmentService
{
    /**
     * @param  list<array{order_line_id: int, quantity: int}>  $lines
     * @param  array<string, mixed>|null  $tracking
     */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        return DB::transaction(function () use ($order, $lines, $tracking) {
            if (! in_array($order->financial_status, ['paid', 'partially_refunded'], true)) {
                throw new FulfillmentGuardException('Fulfillment cannot be created until payment is confirmed.');
            }

            foreach ($lines as $line) {
                $orderLine = $order->lines()->findOrFail($line['order_line_id']);
                $fulfilledSoFar = $this->fulfilledQuantity($orderLine->id);
                $unfulfilled = $orderLine->quantity - $fulfilledSoFar;

                if ($line['quantity'] > $unfulfilled) {
                    throw new InvalidArgumentException('Cannot fulfill more than the ordered quantity.');
                }
            }

            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);

            foreach ($lines as $line) {
                $fulfillment->lines()->create([
                    'order_line_id' => $line['order_line_id'],
                    'quantity' => $line['quantity'],
                ]);
            }

            $this->updateOrderFulfillmentStatus($order);

            FulfillmentCreated::dispatch($fulfillment);

            return $fulfillment;
        });
    }

    /**
     * @param  array<string, mixed>|null  $tracking
     */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null): void
    {
        $fulfillment->update([
            'status' => 'shipped',
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
            'shipped_at' => now(),
        ]);

        FulfillmentShipped::dispatch($fulfillment);
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        $fulfillment->update(['status' => 'delivered', 'delivered_at' => now()]);

        FulfillmentDelivered::dispatch($fulfillment);
    }

    public function autoFulfillDigital(Order $order): ?Fulfillment
    {
        $lines = $order->lines()->with('variant')->get();

        if ($lines->isEmpty()) {
            return null;
        }

        foreach ($lines as $line) {
            if ($line->variant?->requires_shipping) {
                return null;
            }
        }

        return DB::transaction(function () use ($order, $lines) {
            $fulfillment = Fulfillment::create([
                'order_id' => $order->id,
                'status' => 'delivered',
                'shipped_at' => now(),
                'delivered_at' => now(),
            ]);

            foreach ($lines as $line) {
                $fulfillment->lines()->create([
                    'order_line_id' => $line->id,
                    'quantity' => $line->quantity,
                ]);
            }

            $order->update(['fulfillment_status' => 'fulfilled', 'status' => 'fulfilled']);

            return $fulfillment;
        });
    }

    private function fulfilledQuantity(int $orderLineId): int
    {
        return FulfillmentLine::where('order_line_id', $orderLineId)->sum('quantity');
    }

    private function updateOrderFulfillmentStatus(Order $order): void
    {
        $allFulfilled = true;

        foreach ($order->lines()->get() as $line) {
            $fulfilled = FulfillmentLine::where('order_line_id', $line->id)
                ->whereHas('fulfillment', fn ($query) => $query->where('order_id', $order->id))
                ->sum('quantity');

            if ($fulfilled < $line->quantity) {
                $allFulfilled = false;

                break;
            }
        }

        if ($allFulfilled) {
            $order->update(['fulfillment_status' => 'fulfilled', 'status' => 'fulfilled']);
        } else {
            $order->update(['fulfillment_status' => 'partial']);
        }
    }
}
