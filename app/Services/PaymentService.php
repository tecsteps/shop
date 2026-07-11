<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Models\Order;
use App\ValueObjects\PaymentResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentProvider $provider,
        private readonly InventoryService $inventoryService,
        private readonly OrderService $orderService,
    ) {}

    /** @param array<string, mixed> $details */
    public function charge(Checkout $checkout, array $details): PaymentResult
    {
        $result = $this->provider->charge($checkout, $checkout->payment_method, $details);

        if (! $result->success) {
            throw new PaymentFailedException($result->errorCode ?? 'card_declined');
        }

        return $result;
    }

    public function confirmBankTransfer(Order $order): void
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer || $order->financial_status !== FinancialStatus::Pending) {
            throw ValidationException::withMessages(['order' => 'This order is not awaiting a bank transfer.']);
        }

        DB::transaction(function () use ($order): void {
            foreach ($order->lines()->with('variant.inventoryItem')->get() as $line) {
                $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
            }

            $order->payments()->where('status', PaymentStatus::Pending)->update(['status' => PaymentStatus::Captured]);
            $order->update(['financial_status' => FinancialStatus::Paid, 'status' => OrderStatus::Paid]);

            if ($order->lines->every(fn ($line): bool => ! $line->variant->requires_shipping)) {
                $this->orderService->autoFulfillDigitalOrder($order);
            }

            OrderPaid::dispatch($order);
        });
    }
}
