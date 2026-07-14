<?php

namespace App\Services;

use App\Events\FulfillmentCreated;
use App\Events\FulfillmentDelivered;
use App\Events\FulfillmentShipped;
use App\Events\OrderFulfilled;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use BackedEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FulfillmentService
{
    /** @param array<int, int> $lines @param array<string, mixed>|null $tracking */
    public function create(Order $order, array $lines, ?array $tracking = null): Fulfillment
    {
        if (! in_array($this->value($order->financial_status), ['paid', 'partially_refunded'], true)) {
            throw new FulfillmentGuardException('Fulfillment cannot be created until payment is confirmed.');
        }

        return DB::transaction(function () use ($order, $lines, $tracking): Fulfillment {
            $order->load('lines.fulfillmentLines');
            foreach ($lines as $lineId => $quantity) {
                $line = $order->lines->firstWhere('id', (int) $lineId);
                $fulfilled = $line?->fulfillmentLines->sum('quantity') ?? 0;
                if ($line === null || $quantity < 1 || $quantity > $line->quantity - $fulfilled) {
                    throw ValidationException::withMessages(['lines' => 'A fulfillment quantity exceeds the unfulfilled quantity.']);
                }
            }

            $fulfillment = $order->fulfillments()->create([
                'status' => 'pending',
                'tracking_company' => $tracking['tracking_company'] ?? null,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_url' => $tracking['tracking_url'] ?? null,
            ]);
            foreach ($lines as $lineId => $quantity) {
                $fulfillment->lines()->create(['order_line_id' => (int) $lineId, 'quantity' => $quantity]);
            }
            $this->refreshOrderStatus($order);
            event(new FulfillmentCreated($fulfillment));

            return $fulfillment->refresh()->load('lines');
        });
    }

    /** @param array<string, mixed>|null $tracking */
    public function markAsShipped(Fulfillment $fulfillment, ?array $tracking = null, bool $notifyCustomer = true): void
    {
        if ($this->value($fulfillment->status) !== 'pending') {
            throw new FulfillmentGuardException('Only pending fulfillments can be shipped.');
        }
        $fulfillment->fill(array_filter([
            'status' => 'shipped',
            'tracking_company' => $tracking['tracking_company'] ?? $fulfillment->tracking_company,
            'tracking_number' => $tracking['tracking_number'] ?? $fulfillment->tracking_number,
            'tracking_url' => $tracking['tracking_url'] ?? $fulfillment->tracking_url,
            'shipped_at' => now(),
        ], fn (mixed $value): bool => $value !== null))->save();
        event(new FulfillmentShipped($fulfillment, $notifyCustomer));
    }

    public function markAsDelivered(Fulfillment $fulfillment): void
    {
        if ($this->value($fulfillment->status) !== 'shipped') {
            throw new FulfillmentGuardException('Only shipped fulfillments can be delivered.');
        }
        $fulfillment->update(['status' => 'delivered']);
        event(new FulfillmentDelivered($fulfillment));
    }

    public function autoFulfillDigital(Order $order): ?Fulfillment
    {
        $order->loadMissing('lines.variant');
        if ($order->lines->isEmpty() || $order->lines->contains(fn ($line): bool => (bool) $line->variant?->requires_shipping)) {
            return null;
        }

        $fulfillment = $order->fulfillments()->create(['status' => 'delivered', 'shipped_at' => now()]);
        foreach ($order->lines as $line) {
            $fulfillment->lines()->create(['order_line_id' => $line->id, 'quantity' => $line->quantity]);
        }
        $order->update(['fulfillment_status' => 'fulfilled', 'status' => 'fulfilled']);
        event(new OrderFulfilled($order));

        return $fulfillment;
    }

    private function refreshOrderStatus(Order $order): void
    {
        $order->refresh()->load('lines.fulfillmentLines');
        $fulfilled = $order->lines->every(fn ($line): bool => $line->fulfillmentLines->sum('quantity') >= $line->quantity);
        $any = $order->lines->contains(fn ($line): bool => $line->fulfillmentLines->sum('quantity') > 0);
        $order->update([
            'fulfillment_status' => $fulfilled ? 'fulfilled' : ($any ? 'partial' : 'unfulfilled'),
            'status' => $fulfilled ? 'fulfilled' : $order->status,
        ]);
        if ($fulfilled) {
            event(new OrderFulfilled($order));
        }
    }

    private function value(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
