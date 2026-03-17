<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        protected PaymentProvider $paymentProvider,
        protected InventoryService $inventoryService,
    ) {}

    public function create(
        Order $order,
        Payment $payment,
        int $amount,
        ?string $reason = null,
        bool $restock = false,
    ): Refund {
        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock) {
            // 1. Calculate remaining refundable amount
            $totalRefunded = $order->refunds()->sum('amount');
            $refundable = $order->total_amount - $totalRefunded;

            if ($amount > $refundable) {
                throw new \RuntimeException("Refund amount ({$amount}) exceeds refundable amount ({$refundable}).");
            }

            if ($amount > $payment->amount) {
                throw new \RuntimeException("Refund amount ({$amount}) exceeds payment amount ({$payment->amount}).");
            }

            // 2. Call payment provider
            $result = $this->paymentProvider->refund($payment, $amount);

            // 3. Create refund record
            $refund = Refund::query()->create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $result->providerRefundId,
            ]);

            if (! $result->success) {
                return $refund;
            }

            // 4. Update financial status
            $newTotalRefunded = $totalRefunded + $amount;

            if ($newTotalRefunded >= $order->total_amount) {
                $order->update([
                    'financial_status' => FinancialStatus::Refunded,
                    'status' => OrderStatus::Refunded,
                ]);

                $payment->update(['status' => \App\Enums\PaymentStatus::Refunded]);
            } else {
                $order->update([
                    'financial_status' => FinancialStatus::PartiallyRefunded,
                ]);
            }

            // 5. Restock if requested
            if ($restock) {
                $order->load('lines.variant');

                foreach ($order->lines as $line) {
                    if (! $line->variant) {
                        continue;
                    }

                    $inventoryItem = InventoryItem::query()
                        ->withoutGlobalScopes()
                        ->where('variant_id', $line->variant_id)
                        ->first();

                    if ($inventoryItem) {
                        $this->inventoryService->restock($inventoryItem, $line->quantity);
                    }
                }
            }

            // 6. Dispatch event
            OrderRefunded::dispatch($order);

            return $refund;
        });
    }
}
