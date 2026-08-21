<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(private readonly PaymentProvider $provider, private readonly InventoryService $inventory, private readonly AuditLogger $audit) {}

    /**
     * @param  int|array<int, int>|null  $amount
     * @param  array<int, int>  $lines
     */
    public function create(Order $order, Payment $payment, int|array|null $amount = null, ?string $reason = null, bool $restock = false, array $lines = []): Refund
    {
        if ((int) $payment->order_id !== (int) $order->getKey() || $payment->status !== PaymentStatus::Captured) {
            throw new \InvalidArgumentException('The payment is not refundable for this order.');
        }

        if (is_array($amount)) {
            $lines = $amount;
            $amount = null;
        }

        $refunded = (int) $order->refunds()->where('status', 'processed')->sum('amount');
        $order->loadMissing('lines');
        $restockLines = $lines;
        $previousLineQuantities = [];
        foreach ($order->refunds()->where('status', 'processed')->get(['lines_json']) as $previousRefund) {
            foreach ($previousRefund->lines_json ?? [] as $lineId => $quantity) {
                $previousLineQuantities[(string) $lineId] = ($previousLineQuantities[(string) $lineId] ?? 0) + (int) $quantity;
            }
        }

        if ($lines !== []) {
            $amount = 0;

            foreach ($lines as $lineId => $quantity) {
                $orderLine = $order->lines->firstWhere('id', (int) $lineId);

                if ($orderLine === null || $quantity < 1 || $quantity > $orderLine->quantity) {
                    throw new \InvalidArgumentException('The refund quantity is invalid.');
                }

                if (($previousLineQuantities[(string) $lineId] ?? 0) + $quantity > $orderLine->quantity) {
                    throw new \InvalidArgumentException('The refund quantity exceeds the remaining refundable quantity.');
                }

                $unitAmount = intdiv($orderLine->line_total_amount, $orderLine->quantity);
                $amount += $unitAmount * $quantity;
            }
        }

        $amount ??= $payment->amount - $refunded;

        if ($amount < 1 || $refunded + $amount > $payment->amount) {
            throw new \InvalidArgumentException('Refund amount exceeds the captured payment.');
        }

        if ($restock && $restockLines === [] && $refunded + $amount >= $payment->amount) {
            $restockLines = $order->lines->mapWithKeys(fn ($line): array => [$line->getKey() => $line->quantity])->all();
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $restockLines, $lines): Refund {
            $result = $this->provider->refund($payment, $amount);
            $refund = $order->refunds()->create(['payment_id' => $payment->getKey(), 'amount' => $amount, 'status' => $result->successful ? 'processed' : 'failed', 'provider_refund_id' => $result->reference, 'reason' => $reason, 'restock' => $restock, 'lines_json' => $lines !== [] ? $lines : null]);

            if ($result->successful) {
                $totalRefunded = (int) $order->refunds()->where('status', 'processed')->sum('amount');
                $order->update([
                    'financial_status' => $totalRefunded >= $payment->amount ? FinancialStatus::Refunded : FinancialStatus::PartiallyRefunded,
                    'status' => $totalRefunded >= $payment->amount ? 'refunded' : $order->status,
                ]);
                $payment->update(['status' => $totalRefunded >= $payment->amount ? PaymentStatus::Refunded : $payment->status]);

                if ($restock && $restockLines !== []) {
                    $order->load('lines.variant.inventory');
                    foreach ($restockLines as $lineId => $quantity) {
                        $line = $order->lines->firstWhere('id', (int) $lineId);

                        if ($line->variant?->inventory !== null) {
                            $this->inventory->restock($line->variant->inventory, (int) $quantity);
                        }
                    }
                }

                OrderRefunded::dispatch($order->refresh());
                $this->audit->record('order.refunded', $order, ['store_id' => $order->store_id, 'order_number' => $order->order_number, 'amount' => $amount]);
            }

            return $refund;
        });
    }
}
