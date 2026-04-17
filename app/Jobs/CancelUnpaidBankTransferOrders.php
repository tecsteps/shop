<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Models\Order;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    public function handle(InventoryService $inventoryService): void
    {
        $stores = Store::withoutGlobalScopes()->get();

        foreach ($stores as $store) {
            $cancelDays = 7;

            // Check store settings for configurable timeout
            $settings = $store->settings;
            if ($settings) {
                $settingsJson = is_array($settings->settings_json)
                    ? $settings->settings_json
                    : json_decode($settings->settings_json, true);
                $cancelDays = $settingsJson['bank_transfer_cancel_days'] ?? 7;
            }

            $cutoff = now()->subDays($cancelDays);

            $orders = Order::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('payment_method', PaymentMethod::BankTransfer->value)
                ->where('financial_status', FinancialStatus::Pending->value)
                ->where('placed_at', '<', $cutoff)
                ->get();

            foreach ($orders as $order) {
                DB::transaction(function () use ($order, $inventoryService) {
                    // Release reserved inventory
                    foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                        if ($line->variant && $line->variant->inventoryItem) {
                            $inventoryService->release($line->variant->inventoryItem, $line->quantity);
                        }
                    }

                    // Update order
                    $order->update([
                        'financial_status' => FinancialStatus::Voided,
                        'status' => OrderStatus::Cancelled,
                    ]);

                    // Update payments
                    $order->payments()->update(['status' => PaymentStatus::Failed]);

                    OrderCancelled::dispatch($order, 'Auto-cancelled: unpaid bank transfer');
                });
            }
        }
    }
}
