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
        protected PaymentProvider $paymentProvider,
        protected InventoryService $inventoryService,
    ) {}

    /**
     * Process a refund against a captured payment (spec 05 section 11.4).
     * Partial refunds set financial_status to partially_refunded; refunding
     * the full order total sets it to refunded. The restock flag returns
     * each order line's quantity to stock.
     *
     * @throws ValidationException
     */
    public function create(Order $order, Payment $payment, int $amount, ?string $reason = null, bool $restock = false): Refund
    {
        $refundable = $order->remainingRefundableAmount();

        if ($amount < 1 || $amount > $refundable) {
            throw ValidationException::withMessages([
                'amount' => __('The refund amount must be between 1 and :refundable.', [
                    'refundable' => $refundable,
                ]),
            ]);
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock): Refund {
            $refund = Refund::query()->create([
                'order_id' => $order->getKey(),
                'payment_id' => $payment->getKey(),
                'amount' => $amount,
                'reason' => $reason,
                'status' => RefundStatus::Pending,
            ]);

            $result = $this->paymentProvider->refund($payment, $amount);

            $refund->forceFill([
                'status' => $result->success ? RefundStatus::Processed : RefundStatus::Failed,
                'provider_refund_id' => $result->providerRefundId,
            ])->save();

            $totalRefunded = $order->refundedAmount();

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
                $this->restockOrderLines($order);
            }

            event(new OrderRefunded($order, $refund));

            return $refund->refresh();
        });
    }

    /**
     * Return each refunded line's quantity to on-hand stock.
     */
    protected function restockOrderLines(Order $order): void
    {
        foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
            if ($line->variant?->inventoryItem !== null) {
                $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
            }
        }
    }
}
