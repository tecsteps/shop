<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        $existingRefunds = Refund::where('order_id', $order->id)->sum('amount');
        $refundable = $order->total_amount - $existingRefunds;

        if ($amount > $refundable) {
            throw new InvalidArgumentException("Refund amount ({$amount}) exceeds refundable amount ({$refundable}).");
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Refund amount must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock) {
            $result = $this->paymentProvider->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $result->referenceId,
                'created_at' => now(),
            ]);

            if ($result->success) {
                $totalRefunded = Refund::where('order_id', $order->id)
                    ->where('status', RefundStatus::Processed->value)
                    ->sum('amount');

                if ($totalRefunded >= $order->total_amount) {
                    $order->update([
                        'financial_status' => FinancialStatus::Refunded,
                        'status' => OrderStatus::Refunded,
                    ]);
                } else {
                    $order->update([
                        'financial_status' => FinancialStatus::PartiallyRefunded,
                    ]);
                }

                if ($restock) {
                    $order->loadMissing(['lines.variant.inventoryItem']);
                    foreach ($order->lines as $line) {
                        if ($line->variant?->inventoryItem) {
                            $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                        }
                    }
                }

                OrderRefunded::dispatch($order);
            }

            return $refund;
        });
    }
}
