<?php

namespace App\Services;

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

/**
 * Refund processing (spec 05 §11.4): validates against the refundable
 * amount, delegates to the payment provider, updates the order's financial
 * status and optionally restocks inventory.
 */
class RefundService
{
    public function __construct(
        private PaymentService $payments,
        private InventoryService $inventory,
    ) {}

    /**
     * Create a refund for the given amount (integer cents). The mock provider
     * confirms synchronously, so the refund is recorded as processed.
     *
     * @throws ValidationException amount exceeds the refundable remainder
     */
    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        $refundable = $order->refundableAmount();

        if ($amount <= 0 || $amount > $refundable) {
            throw ValidationException::withMessages([
                'amount' => ["The refund amount exceeds the refundable amount of {$refundable}."],
            ]);
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock): Refund {
            $result = $this->payments->refund($payment, $amount);

            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => RefundStatus::Processed,
                'provider_refund_id' => $result->providerRefundId,
            ]);

            $totalRefunded = (int) $order->refunds()
                ->where('status', RefundStatus::Processed->value)
                ->sum('amount');

            if ($totalRefunded >= $order->total_amount) {
                $order->forceFill([
                    'financial_status' => FinancialStatus::Refunded,
                    'status' => OrderStatus::Refunded,
                ])->save();

                $payment->forceFill(['status' => PaymentStatus::Refunded])->save();
            } else {
                $order->forceFill(['financial_status' => FinancialStatus::PartiallyRefunded])->save();
            }

            if ($restock) {
                $order->loadMissing('lines.variant.inventoryItem');

                foreach ($order->lines as $line) {
                    $item = $line->variant?->inventoryItem;

                    if ($item !== null) {
                        $this->inventory->restock($item, $line->quantity);
                    }
                }
            }

            OrderRefunded::dispatch($order->refresh(), $refund);

            return $refund;
        });
    }
}
