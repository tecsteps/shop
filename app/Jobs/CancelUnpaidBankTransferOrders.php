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
        $orders = Order::withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('financial_status', FinancialStatus::Pending->value)
            ->where('status', OrderStatus::Pending->value)
            ->get();

        foreach ($orders as $order) {
            $cancelDays = $this->getCancelDays($order);
            $placedAt = $order->placed_at ? \Carbon\Carbon::parse($order->placed_at) : $order->created_at;

            if (! $placedAt || $placedAt->greaterThan(now()->subDays($cancelDays))) {
                continue;
            }

            DB::transaction(function () use ($order, $inventoryService) {
                // Release reserved inventory
                $order->load('lines.variant.inventoryItem');
                foreach ($order->lines as $line) {
                    if ($line->variant?->inventoryItem) {
                        $inventoryService->release($line->variant->inventoryItem, $line->quantity);
                    }
                }

                $order->update([
                    'financial_status' => FinancialStatus::Voided,
                    'status' => OrderStatus::Cancelled,
                ]);

                $order->payments()
                    ->where('status', PaymentStatus::Pending->value)
                    ->update(['status' => PaymentStatus::Failed->value]);

                OrderCancelled::dispatch($order);
            });
        }
    }

    private function getCancelDays(Order $order): int
    {
        $store = $order->store;

        if ($store) {
            $settings = $store->settings;
            if ($settings) {
                $settingsJson = $settings->settings_json ?? [];
                if (isset($settingsJson['bank_transfer_cancel_days'])) {
                    return (int) $settingsJson['bank_transfer_cancel_days'];
                }
            }
        }

        return 7;
    }
}
