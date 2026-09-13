<?php

namespace App\Jobs;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderCancelled;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CancelUnpaidBankTransferOrders implements ShouldQueue
{
    use Queueable;

    public function __construct(protected InventoryService $inventory) {}

    public function handle(): void
    {
        Order::query()
            ->withoutGlobalScopes()
            ->where('payment_method', PaymentMethod::BankTransfer->value)
            ->where('financial_status', FinancialStatus::Pending->value)
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $cancelDays = $this->cancelDays($order->store_id);

                    if ($order->placed_at === null || $order->placed_at->gt(now()->subDays($cancelDays))) {
                        continue;
                    }

                    DB::transaction(function () use ($order): void {
                        foreach ($order->orderLines()->get() as $line) {
                            if ($line->variant_id === null) {
                                continue;
                            }

                            $item = \App\Models\InventoryItem::query()
                                ->withoutGlobalScopes()
                                ->where('store_id', $order->store_id)
                                ->where('variant_id', $line->variant_id)
                                ->first();

                            if ($item !== null) {
                                $this->inventory->release($item, $line->quantity);
                            }
                        }

                        $order->financial_status = FinancialStatus::Voided;
                        $order->status = OrderStatus::Cancelled;
                        $order->save();

                        $order->payments()->where('status', PaymentStatus::Pending->value)
                            ->update(['status' => PaymentStatus::Failed->value]);

                        event(new OrderCancelled($order->refresh()));
                    });
                }
            });
    }

    protected function cancelDays(int $storeId): int
    {
        $settings = StoreSetting::query()->where('store_id', $storeId)->first()?->settings_json ?? [];

        return (int) ($settings['bank_transfer_cancel_days'] ?? 7);
    }
}
