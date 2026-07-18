<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider,
        private readonly InventoryService $inventoryService,
    ) {}

    /** @param array{amount?: int, lines?: array<int, int>, reason?: string, restock?: bool} $request */
    public function process(Order $order, array $request = []): Refund
    {
        return DB::transaction(function () use ($order, $request): Refund {
            $order->loadMissing(['payments', 'lines.variant.inventoryItem']);
            $refunded = $order->refunds()->where('status', RefundStatus::Processed)->sum('amount');
            $refundable = $order->total_amount - $refunded;
            $lines = $request['lines'] ?? [];

            if (isset($request['amount'])) {
                $amount = $request['amount'];
            } elseif ($lines !== []) {
                $amount = 0;

                foreach ($lines as $lineId => $quantity) {
                    $line = $order->lines->find($lineId);

                    if (! $line || $quantity <= 0 || $quantity > $line->quantity) {
                        throw ValidationException::withMessages(['lines' => 'Invalid refund line quantity.']);
                    }

                    $amount += $line->unit_price_amount * $quantity;
                }
            } else {
                $amount = $refundable;
            }

            if ($amount <= 0 || $amount > $refundable) {
                throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the refundable balance.']);
            }

            $payment = $order->payments->firstWhere('status', 'captured') ?? $order->payments->firstOrFail();
            $result = $this->paymentProvider->refund($payment, $amount);
            $refund = $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $request['reason'] ?? null,
                'status' => $result->status,
                'provider_refund_id' => $result->providerRefundId,
            ]);

            if (! $result->success) {
                $refund->update(['status' => RefundStatus::Failed]);

                return $refund;
            }

            $newTotalRefunded = $refunded + $amount;
            $order->update([
                'financial_status' => $newTotalRefunded === $order->total_amount
                    ? FinancialStatus::Refunded
                    : FinancialStatus::PartiallyRefunded,
                'status' => $newTotalRefunded === $order->total_amount
                    ? OrderStatus::Refunded
                    : $order->status,
            ]);

            if ($newTotalRefunded === $order->total_amount) {
                $payment->update(['status' => PaymentStatus::Refunded]);
            }

            if (($request['restock'] ?? false) && $lines !== []) {
                foreach ($lines as $lineId => $quantity) {
                    $line = $order->lines->find($lineId);

                    if ($line?->variant?->inventoryItem) {
                        $this->inventoryService->restock($line->variant->inventoryItem, $quantity);
                    }
                }
            }

            OrderRefunded::dispatch($order, $refund);

            return $refund;
        });
    }
}
