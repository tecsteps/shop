<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private readonly PaymentProvider $payments,
        private readonly InventoryService $inventory,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock): Refund {
            $order = Order::withoutGlobalScopes()
                ->with('lines.variant.inventoryItem', 'refunds')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payment = Payment::query()
                ->where('order_id', $order->id)
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== PaymentStatus::Captured) {
                throw new RefundException('payment_not_captured', 'Only captured payments can be refunded.');
            }

            $refundable = $order->total_amount - (int) $order->refunds->where('status', RefundStatus::Processed)->sum('amount');

            if ($amount < 1 || $amount > $refundable) {
                throw new RefundException('invalid_refund_amount', 'The refund amount exceeds the remaining refundable amount.');
            }

            $result = $this->payments->refund($payment, $amount);
            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->status,
                'provider_refund_id' => $result->reference,
            ]);

            if (! $result->success) {
                throw new RefundException(
                    $result->errorCode ?? 'refund_failed',
                    $result->message ?? 'The refund could not be processed.',
                );
            }

            $totalRefunded = (int) $order->refunds()->where('status', RefundStatus::Processed)->sum('amount');
            $fullyRefunded = $totalRefunded >= $order->total_amount;

            $order->forceFill([
                'financial_status' => $fullyRefunded ? FinancialStatus::Refunded : FinancialStatus::PartiallyRefunded,
                'status' => $fullyRefunded ? OrderStatus::Refunded : $order->status,
            ])->save();

            if ($fullyRefunded) {
                $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
            }

            if ($restock) {
                $this->restockOrder($order);
            }

            OrderRefunded::dispatch($order, $refund);

            return $refund->refresh();
        });
    }

    private function restockOrder(Order $order): void
    {
        foreach ($order->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->restock($item, $line->quantity);
            }
        }
    }
}
