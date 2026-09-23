<?php

namespace App\Services;

use App\Events\FulfillmentCreated;
use App\Models\Fulfillment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    /** @param array<int|string, int> $lineQuantities
     * @param  array{tracking_company?: ?string, tracking_number?: ?string, tracking_url?: ?string}  $trackingData
     */
    public function create(Order $order, array $lineQuantities, array $trackingData = []): Fulfillment
    {
        return DB::transaction(function () use ($order, $lineQuantities, $trackingData): Fulfillment {
            $order = Order::withoutGlobalScopes()->with('lines.fulfillmentLines')->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->financial_status, ['paid', 'partially_refunded'], true)) {
                throw ValidationException::withMessages(['order' => 'Fulfillment is available after payment is confirmed.']);
            }

            if ($lineQuantities === []) {
                throw ValidationException::withMessages(['lines' => 'Select at least one item to fulfill.']);
            }

            $selectedLines = [];

            foreach ($lineQuantities as $lineId => $quantity) {
                $line = $order->lines->firstWhere('id', (int) $lineId);

                if (! $line || $quantity < 1) {
                    throw ValidationException::withMessages(['lines' => 'Each fulfillment line must belong to this order and have a positive quantity.']);
                }

                $fulfilledQuantity = (int) $line->fulfillmentLines->sum('quantity');

                if ($quantity > $line->quantity - $fulfilledQuantity) {
                    throw ValidationException::withMessages(['lines' => 'A fulfillment cannot exceed the remaining quantity for an order line.']);
                }

                $selectedLines[] = [$line, $quantity];
            }

            $fulfillment = $order->fulfillments()->create([
                'status' => 'pending',
                'tracking_company' => $trackingData['tracking_company'] ?? null,
                'tracking_number' => $trackingData['tracking_number'] ?? null,
                'tracking_url' => $trackingData['tracking_url'] ?? null,
                'created_at' => now(),
            ]);

            foreach ($selectedLines as [$line, $quantity]) {
                $fulfillment->lines()->create(['order_line_id' => $line->id, 'quantity' => $quantity]);
            }

            $fullyFulfilled = $order->lines->every(function ($line) use ($selectedLines): bool {
                $newQuantity = collect($selectedLines)->first(fn (array $selected): bool => $selected[0]->id === $line->id)[1] ?? 0;

                return $line->fulfillmentLines->sum('quantity') + $newQuantity >= $line->quantity;
            });
            $order->forceFill([
                'fulfillment_status' => $fullyFulfilled ? 'fulfilled' : 'partial',
                'status' => $fullyFulfilled ? 'fulfilled' : $order->status,
            ])->save();

            FulfillmentCreated::dispatch($fulfillment->load('order', 'lines.orderLine'));

            return $fulfillment->load('lines.orderLine');
        });
    }

    public function markShipped(Fulfillment $fulfillment): Fulfillment
    {
        if ($fulfillment->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending fulfillments can be marked as shipped.']);
        }

        $fulfillment->forceFill(['status' => 'shipped', 'shipped_at' => now()])->save();

        return $fulfillment->refresh();
    }

    public function markDelivered(Fulfillment $fulfillment): Fulfillment
    {
        if ($fulfillment->status !== 'shipped') {
            throw ValidationException::withMessages(['status' => 'Only shipped fulfillments can be marked as delivered.']);
        }

        $fulfillment->forceFill(['status' => 'delivered', 'delivered_at' => now()])->save();

        return $fulfillment->refresh();
    }
}
