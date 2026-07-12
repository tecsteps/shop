<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Events\OrderRefunded;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

final class RefundService
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly InventoryService $inventory,
    ) {}

    /** @param array<int, int>|null $lines */
    public function create(
        Order $order,
        Payment $payment,
        int $amount,
        ?string $reason = null,
        bool $restock = false,
        ?array $lines = null,
    ): Refund {
        $refunded = (int) $order->refunds()->where('status', 'processed')->sum('amount');
        $remaining = min((int) $order->total_amount, (int) $payment->amount) - $refunded;
        if ($amount < 1 || $amount > $remaining) {
            throw new DomainException('The refund amount exceeds the refundable balance.');
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $lines): Refund {
            $result = $this->provider->refund($payment, $amount);
            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->success ? 'processed' : 'failed',
                'provider_refund_id' => $result->providerRefundId,
            ]);

            $total = (int) $order->refunds()->where('status', 'processed')->sum('amount');
            $full = $total >= $order->total_amount;
            $order->update(['financial_status' => $full ? 'refunded' : 'partially_refunded', 'status' => $full ? 'refunded' : $order->status]);
            if ($full) {
                $payment->update(['status' => 'refunded']);
            }

            if ($restock) {
                $order->load('lines.variant.inventoryItem');
                foreach ($order->lines as $line) {
                    $quantity = $lines === null ? (int) $line->quantity : (int) ($lines[$line->id] ?? 0);
                    if ($quantity > 0 && $line->variant?->inventoryItem !== null) {
                        $this->inventory->restock($line->variant->inventoryItem, min($quantity, (int) $line->quantity));
                    }
                }
            }
            event(new OrderRefunded($order, $refund));

            return $refund->refresh();
        });
    }
}
