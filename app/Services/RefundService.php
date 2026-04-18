<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        protected PaymentProvider $provider,
        protected InventoryService $inventory,
    ) {}

    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $lines
     */
    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false, array $lines = []): Refund
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Refund amount must be positive.']);
        }

        $remaining = $order->total_amount - $order->totalRefunded();
        if ($amount > $remaining) {
            throw ValidationException::withMessages(['amount' => "Refund amount exceeds refundable balance of {$remaining}."]);
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock, $lines): Refund {
            $providerResult = $this->provider->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $providerResult->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $providerResult->providerRefundId,
                'created_at' => now(),
            ]);

            $totalRefunded = $order->fresh()->totalRefunded();
            if ($totalRefunded >= $order->total_amount) {
                $order->financial_status = FinancialStatus::Refunded;
                $order->status = OrderStatus::Refunded;
            } else {
                $order->financial_status = FinancialStatus::PartiallyRefunded;
            }
            $order->save();

            if ($totalRefunded >= $order->total_amount && $payment->status !== PaymentStatus::Refunded) {
                $payment->status = PaymentStatus::Refunded;
                $payment->save();
            }

            if ($restock) {
                $this->restockLines($order, $lines);
            }

            OrderRefunded::dispatch($order, $refund);

            return $refund;
        });
    }

    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $lines
     */
    protected function restockLines(Order $order, array $lines): void
    {
        $order->loadMissing('lines.variant.inventoryItem');

        if (empty($lines)) {
            foreach ($order->lines as $line) {
                $item = $line->variant?->inventoryItem;
                if ($item) {
                    $this->inventory->restock($item, $line->quantity);
                }
            }

            return;
        }

        foreach ($lines as $entry) {
            $line = $order->lines->firstWhere('id', $entry['order_line_id'] ?? null);
            $qty = (int) ($entry['quantity'] ?? 0);
            $item = $line?->variant?->inventoryItem;
            if ($line && $item && $qty > 0) {
                $this->inventory->restock($item, $qty);
            }
        }
    }
}
