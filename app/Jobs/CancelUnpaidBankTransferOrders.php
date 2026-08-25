<?php

namespace App\Jobs;

use App\Events\OrderCancelled;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    public function handle(InventoryService $inventoryService): void
    {
        $days = 7;

        Order::where('payment_method', 'bank_transfer')
            ->where('financial_status', 'pending')
            ->where('placed_at', '<', now()->subDays($days))
            ->get()
            ->each(function (Order $order) use ($inventoryService) {
                foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                    $inventory = $line->variant?->inventoryItem;

                    if ($inventory) {
                        $inventoryService->release($inventory, $line->quantity);
                    }
                }

                $order->update(['financial_status' => 'voided', 'status' => 'cancelled']);
                $order->payments()->update(['status' => 'failed']);
                OrderCancelled::dispatch($order);
            });
    }
}
