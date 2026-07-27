<?php

namespace App\Listeners;

use App\Enums\FinancialStatus;
use App\Events\OrderCancelled;
use App\Services\InventoryService;

/**
 * Release reserved inventory when an order is cancelled (spec 05 §17).
 *
 * OrderService::cancel() releases reservations itself before dispatching the
 * event (and voids the financial status), so this listener is a backstop for
 * cancellations dispatched through other paths while payment is still
 * pending — the only state where stock is held in reservation.
 */
class ReleaseOrderInventory
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Handle the event.
     */
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;

        if ($order->financial_status !== FinancialStatus::Pending) {
            return;
        }

        $order->loadMissing('lines.variant.inventoryItem');

        foreach ($order->lines as $line) {
            $item = $line->variant?->inventoryItem;

            if ($item !== null) {
                $this->inventory->release($item, $line->quantity);
            }
        }
    }
}
