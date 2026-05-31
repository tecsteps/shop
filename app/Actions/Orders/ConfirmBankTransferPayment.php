<?php

namespace App\Actions\Orders;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\FulfillmentService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Confirms receipt of a bank-transfer payment (admin action).
 *
 * Only valid for a `bank_transfer` order whose financial status is still
 * `pending`. On confirmation the payment is captured, the order moves to
 * paid, reserved inventory is committed, an all-digital order is auto-delivered,
 * and {@see OrderPaid} is dispatched.
 */
class ConfirmBankTransferPayment
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly FulfillmentService $fulfillments,
    ) {}

    public function handle(Order $order): Order
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new RuntimeException('Only bank-transfer orders can have their payment confirmed manually.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new RuntimeException('This order\'s payment has already been confirmed.');
        }

        DB::transaction(function () use ($order): void {
            $order->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Captured->value]);

            $order->update([
                'financial_status' => FinancialStatus::Paid->value,
                'status' => OrderStatus::Paid->value,
            ]);

            $this->commitInventory($order);

            $this->fulfillments->autoFulfillDigital($order->refresh());
        });

        OrderPaid::dispatch($order->refresh());

        return $order;
    }

    private function commitInventory(Order $order): void
    {
        foreach ($order->lines as $line) {
            $item = $line->variant_id === null
                ? null
                : ProductVariant::query()->find($line->variant_id)?->inventoryItem;

            if ($item !== null) {
                $this->inventory->commit($item, $line->quantity);
            }
        }
    }
}
