<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        public PaymentProvider $paymentProvider,
        public InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        $existingRefunds = $payment->refunds()->sum('amount');
        $maxRefundable = $payment->amount - $existingRefunds;

        if ($amount > $maxRefundable) {
            throw new \InvalidArgumentException(
                "Refund amount ({$amount}) exceeds maximum refundable amount ({$maxRefundable})."
            );
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock): Refund {
            $refundResult = $this->paymentProvider->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => RefundStatus::from($refundResult->status),
                'provider_refund_id' => $refundResult->providerRefundId,
            ]);

            $totalRefunded = $payment->refunds()->sum('amount');

            if ($totalRefunded >= $payment->amount) {
                $order->update(['financial_status' => FinancialStatus::Refunded]);
            } else {
                $order->update(['financial_status' => FinancialStatus::PartiallyRefunded]);
            }

            if ($restock) {
                $order->load('lines.variant.inventoryItem');

                foreach ($order->lines as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            OrderRefunded::dispatch($order, $refund);

            return $refund;
        });
    }
}
