<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array{amount?: int, lines?: array<int, int>, reason?: string, restock?: bool}  $request
     */
    public function processRefund(Order $order, array $request): Refund
    {
        return DB::transaction(function () use ($order, $request): Refund {
            $order->load('lines.variant.inventoryItem', 'payments', 'refunds');

            $existingRefunds = $order->refunds->sum('amount');
            $refundable = $order->total_amount - $existingRefunds;

            if (isset($request['amount'])) {
                $amount = $request['amount'];

                if ($amount > $refundable) {
                    throw new InvalidArgumentException("Refund amount ({$amount}) exceeds refundable amount ({$refundable}).");
                }
            } elseif (isset($request['lines'])) {
                $amount = 0;

                foreach ($request['lines'] as $lineId => $qty) {
                    $line = $order->lines->firstWhere('id', $lineId);

                    if (! $line) {
                        throw new InvalidArgumentException("Order line {$lineId} not found.");
                    }

                    $unitAmount = $line->quantity > 0
                        ? (int) round($line->total_amount / $line->quantity)
                        : 0;
                    $amount += $unitAmount * $qty;
                }

                if ($amount > $refundable) {
                    $amount = $refundable;
                }
            } else {
                $amount = $refundable;
            }

            if ($amount <= 0) {
                throw new InvalidArgumentException('Nothing to refund.');
            }

            $payment = $order->payments->first();
            $providerResult = $this->paymentProvider->refund($payment, $amount);

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment?->id,
                'amount' => $amount,
                'currency' => $order->currency,
                'reason' => $request['reason'] ?? null,
                'status' => $providerResult->success ? RefundStatus::Processed : RefundStatus::Failed,
                'reference' => $providerResult->providerRefundId,
                'restock' => $request['restock'] ?? false,
                'processed_at' => $providerResult->success ? now() : null,
            ]);

            if ($providerResult->success) {
                $totalRefunded = $existingRefunds + $amount;

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

                if ($request['restock'] ?? false) {
                    $linesToRestock = $request['lines'] ?? [];

                    if (empty($linesToRestock)) {
                        foreach ($order->lines as $line) {
                            if ($line->variant?->inventoryItem) {
                                $this->inventoryService->restock($line->variant->inventoryItem, $line->quantity);
                            }
                        }
                    } else {
                        foreach ($linesToRestock as $lineId => $qty) {
                            $line = $order->lines->firstWhere('id', $lineId);

                            if ($line?->variant?->inventoryItem) {
                                $this->inventoryService->restock($line->variant->inventoryItem, $qty);
                            }
                        }
                    }
                }

                OrderRefunded::dispatch($order->fresh());
            }

            try {
                $store = Store::find($order->store_id);
                app(WebhookService::class)->dispatch($store, 'refunds/create', $refund->toArray());
            } catch (\Throwable $e) {
                Log::warning('Webhook dispatch failed for refunds/create', ['error' => $e->getMessage()]);
            }

            return $refund;
        });
    }
}
