<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Events\OrderRefunded;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use BackedEnum;
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
        ?int $amount = null,
        ?string $reason = null,
        bool $restock = false,
        ?array $lines = null,
        bool $notifyCustomer = true,
    ): Refund {
        if ($restock && ($lines === null || $lines === [])) {
            throw new DomainException('Explicit line quantities are required when restocking a refund.');
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $lines, $notifyCustomer): Refund {
            $order = Order::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ((int) $payment->order_id !== (int) $order->id || ! in_array($this->value($payment->status), ['captured', 'refunded'], true)) {
                throw new DomainException('The selected payment is not refundable for this order.');
            }

            $order->load('lines');
            $lineAmounts = [];
            foreach ($lines ?? [] as $lineId => $quantity) {
                $line = $order->lines->firstWhere('id', (int) $lineId);
                $alreadyRefunded = $line === null ? 0 : (int) DB::table('refund_lines')
                    ->join('refunds', 'refunds.id', '=', 'refund_lines.refund_id')
                    ->where('refund_lines.order_line_id', $line->id)
                    ->where('refunds.status', 'processed')
                    ->sum('refund_lines.quantity');
                if ($line === null || $quantity < 1 || $quantity > (int) $line->quantity - $alreadyRefunded) {
                    throw new DomainException('A refund quantity exceeds the refundable line quantity.');
                }
                $lineAmounts[(int) $lineId] = (int) round((int) $line->total_amount * $quantity / max(1, (int) $line->quantity));
            }

            $refunded = (int) $order->refunds()->where('status', 'processed')->sum('amount');
            $remaining = min((int) $order->total_amount, (int) $payment->amount) - $refunded;
            $refundAmount = $amount ?? ($lineAmounts === [] ? $remaining : array_sum($lineAmounts));
            if ($refundAmount < 1 || $refundAmount > $remaining) {
                throw new DomainException('The refund amount exceeds the refundable balance.');
            }

            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
                'reason' => $reason,
                'status' => 'pending',
            ]);
            foreach ($lines ?? [] as $lineId => $quantity) {
                $refund->lines()->create([
                    'order_line_id' => (int) $lineId,
                    'quantity' => $quantity,
                    'amount' => $lineAmounts[(int) $lineId],
                ]);
            }
            $result = $this->provider->refund($payment, $refundAmount);
            $refund->update([
                'status' => $result->success ? 'processed' : 'failed',
                'provider_refund_id' => $result->providerRefundId,
            ]);

            if (! $result->success) {
                return $refund->refresh();
            }

            $total = (int) $order->refunds()->where('status', 'processed')->sum('amount');
            $full = $total >= $order->total_amount;
            $order->update(['financial_status' => $full ? 'refunded' : 'partially_refunded', 'status' => $full ? 'refunded' : $order->status]);
            if ($full) {
                $payment->update(['status' => 'refunded']);
            }

            if ($restock) {
                $order->load(['lines.variant.inventoryItem' => fn ($query) => $query->withoutGlobalScopes()]);
                foreach ($order->lines as $line) {
                    $quantity = $lines === null ? (int) $line->quantity : (int) ($lines[$line->id] ?? 0);
                    if ($quantity > 0 && $line->variant?->inventoryItem !== null) {
                        $this->inventory->restock($line->variant->inventoryItem, min($quantity, (int) $line->quantity));
                    }
                }
            }
            event(new OrderRefunded($order, $refund, $notifyCustomer));

            return $refund->refresh()->load('lines');
        });
    }

    private function value(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
