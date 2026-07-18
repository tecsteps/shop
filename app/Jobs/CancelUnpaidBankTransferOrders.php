<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    public function handle(InventoryService $inventoryService): void
    {
        Order::query()
            ->where('payment_method', PaymentMethod::BankTransfer)
            ->where('financial_status', FinancialStatus::Pending)
            ->with(['store.settings', 'lines.variant.inventoryItem', 'payments'])
            ->chunkById(100, function ($orders) use ($inventoryService): void {
                foreach ($orders as $order) {
                    $days = (int) ($order->store->settings?->settings_json['bank_transfer_cancel_days'] ?? 7);

                    if ($order->placed_at->isAfter(now()->subDays($days))) {
                        continue;
                    }

                    DB::transaction(function () use ($order, $inventoryService): void {
                        foreach ($order->lines as $line) {
                            if ($line->variant?->inventoryItem) {
                                $inventoryService->release($line->variant->inventoryItem, $line->quantity);
                            }
                        }

                        $order->payments()->update(['status' => PaymentStatus::Failed]);
                        $order->update([
                            'financial_status' => FinancialStatus::Voided,
                            'status' => OrderStatus::Cancelled,
                        ]);
                        OrderCancelled::dispatch($order);
                    });
                }
            });
    }
}
