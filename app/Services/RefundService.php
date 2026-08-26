<?php

namespace App\Services;

use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function create(Order $order, Payment $payment, int $amount, ?string $reason, bool $restock): Refund
    {
        return DB::transaction(function () use ($order, $payment, $amount, $reason, $restock) {
            $refundable = $order->total_amount - $order->refunds()->sum('amount');

            if ($amount > $refundable) {
                throw new InvalidArgumentException('Refund amount exceeds the refundable total.');
            }

            $result = $this->paymentService->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->success ? 'processed' : 'failed',
                'provider_refund_id' => $result->providerRefundId,
            ]);

            $totalRefunded = $order->refunds()->sum('amount');

            if ($totalRefunded >= $order->total_amount) {
                $order->update(['financial_status' => 'refunded', 'status' => 'refunded']);
            } else {
                $order->update(['financial_status' => 'partially_refunded']);
            }

            if ($restock) {
                $this->restockInventory($order);
            }

            OrderRefunded::dispatch($order);

            return $refund;
        });
    }

    private function restockInventory(Order $order): void
    {
        foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
            $inventory = $line->variant?->inventoryItem;

            if ($inventory) {
                $this->inventoryService->restock($inventory, $line->quantity);
            }
        }
    }
}
