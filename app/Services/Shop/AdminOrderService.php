<?php

namespace App\Services\Shop;

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Validation\ValidationException;

class AdminOrderService
{
    public function refund(Order $order, int $amount, string $reason = 'Customer request'): Refund
    {
        $refunded = Refund::query()->where('order_id', $order->id)->sum('amount');
        $remaining = $order->total_amount - $refunded;

        if ($amount <= 0 || $amount > $remaining) {
            throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the refundable balance.']);
        }

        $refund = Refund::query()->create([
            'store_id' => $order->store_id,
            'order_id' => $order->id,
            'amount' => $amount,
            'reason' => $reason,
        ]);

        $order->update([
            'status' => $amount === $remaining ? 'refunded' : 'partially_refunded',
            'financial_status' => $amount === $remaining ? 'refunded' : 'partially_refunded',
            'timeline_json' => array_merge($order->timeline_json ?? [], [
                ['at' => now()->toISOString(), 'message' => 'Refund processed'],
            ]),
        ]);

        Payment::query()->where('order_id', $order->id)->update(['status' => $order->financial_status]);

        return $refund;
    }

    public function fulfill(Order $order): Fulfillment
    {
        if (! in_array($order->financial_status, ['paid', 'partially_refunded'], true)) {
            throw ValidationException::withMessages(['order' => 'Fulfillment is blocked until payment is paid.']);
        }

        $fulfillment = Fulfillment::query()->create([
            'store_id' => $order->store_id,
            'order_id' => $order->id,
            'status' => 'created',
        ]);

        foreach ($order->lines as $line) {
            $fulfillment->lines()->create([
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $order->update([
            'fulfillment_status' => 'fulfilled',
            'timeline_json' => array_merge($order->timeline_json ?? [], [
                ['at' => now()->toISOString(), 'message' => 'Fulfillment created'],
            ]),
        ]);

        return $fulfillment;
    }

    public function markShipped(Fulfillment $fulfillment): Fulfillment
    {
        $fulfillment->update(['status' => 'shipped', 'shipped_at' => now()]);

        return $fulfillment->refresh();
    }

    public function markDelivered(Fulfillment $fulfillment): Fulfillment
    {
        $fulfillment->update(['status' => 'delivered', 'delivered_at' => now()]);

        return $fulfillment->refresh();
    }
}

