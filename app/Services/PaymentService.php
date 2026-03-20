<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private PaymentProvider $paymentProvider,
        private InventoryService $inventoryService,
    ) {}

    public function confirmBankTransfer(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            throw new RuntimeException('Order is not a bank transfer order.');
        }

        if ($order->financial_status !== FinancialStatus::Pending) {
            throw new RuntimeException('Order financial status is not pending.');
        }

        DB::transaction(function () use ($order) {
            $payment = $order->payments()->where('status', PaymentStatus::Pending)->first();

            if ($payment) {
                $payment->update(['status' => PaymentStatus::Captured]);
            }

            $order->update([
                'financial_status' => FinancialStatus::Paid,
                'status' => OrderStatus::Paid,
            ]);

            // Commit inventory (convert reserved to committed)
            $order->load('lines.variant.inventoryItem');
            foreach ($order->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                }
            }

            // Auto-fulfill if all items are digital
            $this->autoFulfillDigitalProducts($order);

            OrderPaid::dispatch($order);
        });
    }

    public function autoFulfillDigitalProducts(Order $order): void
    {
        $order->load('lines.variant');

        $allDigital = $order->lines->every(
            fn ($line) => $line->variant && ! $line->variant->requires_shipping
        );

        if (! $allDigital || $order->lines->isEmpty()) {
            return;
        }

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'shipped_at' => now()->toIso8601String(),
            'delivered_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ]);

        foreach ($order->lines as $line) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $order->update([
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'status' => OrderStatus::Fulfilled,
        ]);
    }
}
