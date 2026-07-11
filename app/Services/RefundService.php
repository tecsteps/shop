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
    public function __construct(private readonly PaymentProvider $provider, private readonly InventoryService $inventoryService) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        $refundable = $payment->amount - $payment->refunds()->where('status', RefundStatus::Processed)->sum('amount');

        if ($payment->order_id !== $order->id || $amount < 1 || $amount > $refundable) {
            throw ValidationException::withMessages(['amount' => 'The refund amount exceeds the refundable balance.']);
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock): Refund {
            $providerResult = $this->provider->refund($payment, $amount);
            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $providerResult->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $providerResult->providerRefundId,
            ]);

            if (! $providerResult->success) {
                return $refund;
            }

            $totalRefunded = $order->refunds()->where('status', RefundStatus::Processed)->sum('amount');
            $isFullRefund = $totalRefunded >= $order->total_amount;
            $order->update([
                'financial_status' => $isFullRefund ? FinancialStatus::Refunded : FinancialStatus::PartiallyRefunded,
                'status' => $isFullRefund ? OrderStatus::Refunded : $order->status,
            ]);

            if ($isFullRefund) {
                $payment->update(['status' => PaymentStatus::Refunded]);
            }

            if ($restock) {
                foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
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
