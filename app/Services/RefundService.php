<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason, bool $restock): Refund
    {
        $existingRefunds = Refund::where('payment_id', $payment->id)
            ->where('status', '!=', 'failed')
            ->sum('amount');

        $refundable = $payment->amount - $existingRefunds;

        if ($amount > $refundable) {
            throw new \InvalidArgumentException(
                "Refund amount ({$amount}) exceeds refundable amount ({$refundable})."
            );
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock) {
            $refundResult = $this->paymentProvider->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $refundResult->success ? 'processed' : 'failed',
                'provider_refund_id' => $refundResult->providerRefundId,
            ]);

            if ($refundResult->success) {
                // Recalculate total refunded for the order
                $totalRefunded = Refund::where('order_id', $order->id)
                    ->where('status', 'processed')
                    ->sum('amount');

                if ($totalRefunded >= $order->total_amount) {
                    $order->update([
                        'financial_status' => 'refunded',
                        'status' => 'refunded',
                    ]);
                } else {
                    $order->update([
                        'financial_status' => 'partially_refunded',
                    ]);
                }

                // Restock if requested
                if ($restock) {
                    $order->load('lines.variant.inventoryItem');
                    foreach ($order->lines as $line) {
                        if ($line->variant && $line->variant->inventoryItem) {
                            $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                        }
                    }
                }
            }

            OrderRefunded::dispatch($order, $refund);

            return $refund;
        });
    }
}
