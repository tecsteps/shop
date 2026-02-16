<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Order'])]
class Show extends Component
{
    public Order $order;

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    /** @var array<int, array{line_id: int, quantity: int}> */
    public array $fulfillmentLines = [];

    public string $refundAmount = '';

    public string $refundReason = '';

    public bool $refundRestock = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'lines.product',
            'lines.variant',
            'payments',
            'refunds',
            'fulfillments.lines.orderLine',
            'customer',
        ]);

        $this->initFulfillmentLines();
    }

    public function openFulfillmentModal(): void
    {
        $this->initFulfillmentLines();
        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(): void
    {
        $fulfillment = $this->order->fulfillments()->create([
            'tracking_company' => $this->trackingCompany ?: null,
            'tracking_number' => $this->trackingNumber ?: null,
            'status' => FulfillmentShipmentStatus::Pending,
        ]);

        foreach ($this->fulfillmentLines as $fl) {
            if ($fl['quantity'] > 0) {
                $fulfillment->lines()->create([
                    'order_line_id' => $fl['line_id'],
                    'quantity' => $fl['quantity'],
                ]);
            }
        }

        $this->updateFulfillmentStatus();
        $this->showFulfillmentModal = false;
        $this->trackingCompany = '';
        $this->trackingNumber = '';
        $this->order->refresh();
        $this->mount($this->order);
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $this->order->fulfillments()->where('id', $fulfillmentId)->update([
            'status' => FulfillmentShipmentStatus::Shipped,
            'shipped_at' => now(),
        ]);
        $this->order->refresh();
        $this->mount($this->order);
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $this->order->fulfillments()->where('id', $fulfillmentId)->update([
            'status' => FulfillmentShipmentStatus::Delivered,
            'delivered_at' => now(),
        ]);
        $this->order->refresh();
        $this->mount($this->order);
    }

    public function openRefundModal(): void
    {
        $this->refundAmount = (string) ($this->order->total / 100);
        $this->showRefundModal = true;
    }

    public function createRefund(): void
    {
        $this->validate([
            'refundAmount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $payment = $this->order->payments()->where('status', PaymentStatus::Captured)->first();

        $this->order->refunds()->create([
            'payment_id' => $payment?->id,
            'amount' => (int) (((float) $this->refundAmount) * 100),
            'reason' => $this->refundReason ?: null,
            'status' => RefundStatus::Processed,
            'restock' => $this->refundRestock,
            'processed_at' => now(),
        ]);

        $totalRefunded = $this->order->refunds()->sum('amount');
        if ($totalRefunded >= $this->order->total) {
            $this->order->update(['financial_status' => FinancialStatus::Refunded]);
        } else {
            $this->order->update(['financial_status' => FinancialStatus::PartiallyRefunded]);
        }

        $this->showRefundModal = false;
        $this->order->refresh();
        $this->mount($this->order);
    }

    public function confirmPayment(): void
    {
        $this->order->payments()->where('status', PaymentStatus::Pending)->update([
            'status' => PaymentStatus::Captured,
            'captured_at' => now(),
        ]);
        $this->order->update(['financial_status' => FinancialStatus::Paid]);
        $this->order->refresh();
        $this->mount($this->order);
    }

    public function render(): mixed
    {
        return view('livewire.admin.orders.show');
    }

    private function initFulfillmentLines(): void
    {
        $this->fulfillmentLines = [];
        foreach ($this->order->lines as $line) {
            $this->fulfillmentLines[] = [
                'line_id' => $line->id,
                'quantity' => $line->quantity,
            ];
        }
    }

    private function updateFulfillmentStatus(): void
    {
        $totalQty = $this->order->lines->sum('quantity');
        $fulfilledQty = $this->order->fulfillments()
            ->with('lines')
            ->get()
            ->flatMap->lines
            ->sum('quantity');

        if ($fulfilledQty >= $totalQty) {
            $this->order->update(['fulfillment_status' => FulfillmentStatus::Fulfilled]);
        } elseif ($fulfilledQty > 0) {
            $this->order->update(['fulfillment_status' => FulfillmentStatus::Partial]);
        }
    }
}
