<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

class CancelUnpaidBankTransferOrders
{
    public function __invoke(): void
    {
        $inventoryService = app(InventoryService::class);
        $cancelDays = 7;

        $orders = Order::query()
            ->withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer)
            ->where('financial_status', FinancialStatus::Pending)
            ->where('placed_at', '<', now()->subDays($cancelDays))
            ->get();

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $inventoryService) {
                $order->load('lines.variant');

                // Release reserved inventory
                foreach ($order->lines as $line) {
                    if (! $line->variant) {
                        continue;
                    }

                    $inventoryItem = InventoryItem::query()
                        ->withoutGlobalScopes()
                        ->where('variant_id', $line->variant_id)
                        ->first();

                    if ($inventoryItem) {
                        $inventoryService->release($inventoryItem, $line->quantity);
                    }
                }

                // Update order
                $order->update([
                    'financial_status' => FinancialStatus::Voided,
                    'status' => OrderStatus::Cancelled,
                ]);

                // Update payment
                $order->payments()
                    ->where('status', PaymentStatus::Pending)
                    ->update(['status' => PaymentStatus::Failed]);

                OrderCancelled::dispatch($order);
            });
        }
    }
}
