<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    public Order $order;

    public bool $showFulfillmentModal = false;

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public bool $showRefundModal = false;

    public int $refundAmount = 0;

    public string $refundReason = '';

    public bool $restockItems = false;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function openFulfillmentModal(): void
    {
        $this->trackingNumber = '';
        $this->trackingUrl = '';
        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(): void
    {
        /** @var FulfillmentService $service */
        $service = app(FulfillmentService::class);

        $lines = $this->order->lines->map(fn ($line) => [
            'order_line_id' => $line->id,
            'quantity' => $line->quantity,
        ])->toArray();

        $service->create($this->order, $lines, $this->trackingNumber ?: null, $this->trackingUrl ?: null);

        $this->order->refresh();
        $this->showFulfillmentModal = false;
        $this->dispatch('toast', type: 'success', message: 'Fulfillment created successfully.');
    }

    public function openRefundModal(): void
    {
        $this->refundAmount = $this->order->total_amount;
        $this->refundReason = '';
        $this->restockItems = false;
        $this->showRefundModal = true;
    }

    public function createRefund(): void
    {
        if ($this->refundAmount <= 0) {
            return;
        }

        /** @var RefundService $service */
        $service = app(RefundService::class);

        $service->create($this->order, $this->refundAmount, $this->refundReason ?: null);

        $this->order->refresh();
        $this->showRefundModal = false;
        $this->dispatch('toast', type: 'success', message: 'Refund processed successfully.');
    }

    public function render(): View
    {
        $this->order->load(['lines', 'customer', 'payments', 'fulfillments', 'refunds']);

        return view('livewire.admin.orders.show');
    }
}
