<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\RefundStatus;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Show extends Component
{
    public Order $order;

    public bool $showFulfillmentModal = false;

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public bool $showRefundModal = false;

    public int $refundAmount = 0;

    public string $refundReason = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function openFulfillmentModal(): void
    {
        $this->trackingCompany = '';
        $this->trackingNumber = '';
        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $this->order->id,
            'status' => FulfillmentShipmentStatus::Pending,
            'tracking_company' => $this->trackingCompany ?: null,
            'tracking_number' => $this->trackingNumber ?: null,
        ]);

        foreach ($this->order->lines as $line) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $this->order->update([
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
        ]);

        $this->showFulfillmentModal = false;
        $this->order->refresh();
    }

    public function openRefundModal(): void
    {
        $this->refundAmount = $this->order->total_amount;
        $this->refundReason = '';
        $this->showRefundModal = true;
    }

    public function processRefund(): void
    {
        $this->validate([
            'refundAmount' => ['required', 'integer', 'min:1'],
            'refundReason' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = $this->order->payments()->first();

        Refund::create([
            'order_id' => $this->order->id,
            'payment_id' => $payment?->id,
            'amount' => $this->refundAmount,
            'reason' => $this->refundReason ?: null,
            'status' => RefundStatus::Processed,
            'provider_refund_id' => 'mock_ref_'.now()->timestamp,
        ]);

        $this->showRefundModal = false;
        $this->order->refresh();
    }

    public function render(): View
    {
        $this->order->load(['lines', 'payments', 'refunds', 'fulfillments.lines', 'customer']);

        return view('livewire.admin.orders.show', [
            'order' => $this->order,
        ]);
    }
}
