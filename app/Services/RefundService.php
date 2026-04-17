<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason, bool $restock = false): Refund
    {
        $alreadyRefunded = $order->refundedTotal();
        $maxRefundable = (int) $payment->amount - $alreadyRefunded;

        if ($amount <= 0 || $amount > $maxRefundable) {
            throw new InvalidArgumentException('Invalid refund amount.');
        }

        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock): Refund {
            $result = $this->provider->refund($payment, $amount);

            /** @var Refund $refund */
            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->status->value,
                'provider_refund_id' => $result->providerRefundId,
            ]);

            if ($result->status === RefundStatus::Processed) {
                $order->refresh();
                $totalRefunded = $order->refundedTotal();

                $newFinancialStatus = $totalRefunded >= (int) $order->total_amount
                    ? FinancialStatus::Refunded
                    : FinancialStatus::PartiallyRefunded;

                $order->update(['financial_status' => $newFinancialStatus->value]);

                if ($restock) {
                    $order->loadMissing('lines');
                    foreach ($order->lines as $line) {
                        if ($line->variant_id === null) {
                            continue;
                        }

                        $item = InventoryItem::withoutGlobalScopes()
                            ->where('variant_id', $line->variant_id)
                            ->first();

                        if ($item !== null) {
                            $this->inventoryService->restock($item, (int) $line->quantity);
                        }
                    }
                }

                OrderRefunded::dispatch($order->fresh() ?? $order, $refund);
            }

            return $refund;
        });
    }
}
