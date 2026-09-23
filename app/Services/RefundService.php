<?php

namespace App\Services;

use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /** @param array<int|string, int> $lineQuantities */
    public function refund(Order $order, int $amount = 0, ?string $reason = null, bool $restock = false, array $lineQuantities = []): Refund
    {
        return DB::transaction(function () use ($order, $amount, $reason, $restock, $lineQuantities): Refund {
            $order = Order::withoutGlobalScopes()->with('payments', 'lines.variant.inventoryItem', 'lines.refundLines')->lockForUpdate()->findOrFail($order->id);
            $payment = $order->payments->first(fn (Payment $candidate): bool => in_array($candidate->status, ['captured', 'partially_refunded'], true));

            if (! $payment || ! in_array($order->financial_status, ['paid', 'partially_refunded'], true)) {
                throw ValidationException::withMessages(['order' => 'Only a paid order can be refunded.']);
            }

            $alreadyRefunded = (int) $order->refunds()->sum('amount');
            $remaining = max(0, $order->total_amount - $alreadyRefunded);

            if ($lineQuantities !== []) {
                $lineAmount = 0;
                $selectedLines = [];

                foreach ($lineQuantities as $lineId => $quantity) {
                    $line = $order->lines->firstWhere('id', (int) $lineId);
                    $previousQuantity = $line?->refundLines()->sum('quantity') ?? 0;

                    if (! $line || $quantity < 1 || $quantity > $line->quantity - $previousQuantity) {
                        throw ValidationException::withMessages(['lines' => 'Refund quantity must be within the remaining quantity for each order line.']);
                    }

                    $unitAmount = $line->quantity > 0 ? (int) round($line->total_amount / $line->quantity) : $line->unit_price_amount;
                    $lineAmount += $unitAmount * $quantity;
                    $selectedLines[] = [$line, $quantity, $unitAmount * $quantity];
                }

                if ($amount === 0) {
                    $amount = $lineAmount;
                }
            } else {
                $selectedLines = [];
            }

            if ($amount === 0) {
                $amount = $remaining;
            }

            if ($amount < 1 || $amount > $remaining) {
                throw ValidationException::withMessages(['amount' => 'The refund amount must be greater than zero and cannot exceed the remaining refundable balance.']);
            }

            if ($restock && $selectedLines === []) {
                if ($amount !== $remaining) {
                    throw ValidationException::withMessages(['lines' => 'Choose the refunded item quantities before restocking a partial refund.']);
                }

                foreach ($order->lines as $line) {
                    $alreadyRestocked = (int) DB::table('refund_lines')
                        ->join('refunds', 'refunds.id', '=', 'refund_lines.refund_id')
                        ->where('refunds.order_id', $order->id)
                        ->where('refund_lines.order_line_id', $line->id)
                        ->sum('refund_lines.quantity');
                    $remainingQuantity = $line->quantity - $alreadyRestocked;

                    if ($remainingQuantity > 0) {
                        $selectedLines[] = [$line, $remainingQuantity, $line->total_amount];
                    }
                }
            }

            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'processed',
                'provider_refund_id' => 'mock_refund_'.Str::random(18),
                'created_at' => now(),
            ]);

            foreach ($selectedLines as [$line, $quantity, $lineRefundAmount]) {
                $refund->lines()->create([
                    'order_line_id' => $line->id,
                    'quantity' => $quantity,
                    'amount' => min($lineRefundAmount, $amount),
                ]);

                if ($restock && $line->variant?->inventoryItem) {
                    $this->inventory->restock($line->variant->inventoryItem, $quantity);
                }
            }

            $totalRefunded = $alreadyRefunded + $amount;
            $fullyRefunded = $totalRefunded >= $order->total_amount;
            $payment->forceFill(['status' => $fullyRefunded ? 'refunded' : 'partially_refunded'])->save();
            $order->forceFill([
                'financial_status' => $fullyRefunded ? 'refunded' : 'partially_refunded',
                'status' => $fullyRefunded ? 'refunded' : $order->status,
            ])->save();

            OrderRefunded::dispatch($order->refresh(), $refund);

            return $refund->load('lines.orderLine');
        });
    }
}
