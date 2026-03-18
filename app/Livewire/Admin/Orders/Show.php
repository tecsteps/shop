<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    public Order $order;

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    /** @var array<int, int> */
    public array $fulfillmentQuantities = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public int $refundAmount = 0;

    public string $refundReason = '';

    public bool $refundRestock = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'lines',
            'payments',
            'refunds',
            'fulfillments.lines',
            'customer',
        ]);

        foreach ($this->order->lines as $line) {
            $fulfilledQty = $line->fulfillmentLines->sum('quantity');
            $remaining = $line->quantity - $fulfilledQty;
            $this->fulfillmentQuantities[$line->id] = max(0, $remaining);
        }
    }

    public function openFulfillmentModal(): void
    {
        $this->order->load('lines.fulfillmentLines');

        foreach ($this->order->lines as $line) {
            $fulfilledQty = $line->fulfillmentLines->sum('quantity');
            $remaining = $line->quantity - $fulfilledQty;
            $this->fulfillmentQuantities[$line->id] = max(0, $remaining);
        }

        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(FulfillmentService $fulfillmentService): void
    {
        $lines = array_filter($this->fulfillmentQuantities, fn ($qty) => $qty > 0);

        if (empty($lines)) {
            $this->dispatch('toast', type: 'error', message: __('No items selected for fulfillment.'));

            return;
        }

        $tracking = null;
        if ($this->trackingNumber) {
            $tracking = [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber,
                'tracking_url' => $this->trackingUrl ?: null,
            ];
        }

        try {
            $fulfillmentService->create($this->order, $lines, $tracking);
            $this->showFulfillmentModal = false;
            $this->trackingCompany = '';
            $this->trackingNumber = '';
            $this->trackingUrl = '';
            $this->order->refresh();
            $this->order->load(['lines.fulfillmentLines', 'fulfillments.lines']);
            $this->dispatch('toast', type: 'success', message: __('Fulfillment created.'));
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function openRefundModal(): void
    {
        $existingRefunds = $this->order->refunds->sum('amount');
        $this->refundAmount = $this->order->total_amount - $existingRefunds;
        $this->showRefundModal = true;
    }

    public function createRefund(RefundService $refundService): void
    {
        if ($this->refundAmount <= 0) {
            $this->dispatch('toast', type: 'error', message: __('Refund amount must be greater than zero.'));

            return;
        }

        $payment = $this->order->payments()->where('status', PaymentStatus::Captured)->first();

        if (! $payment) {
            $this->dispatch('toast', type: 'error', message: __('No captured payment found to refund.'));

            return;
        }

        try {
            $refundService->create(
                $this->order,
                $payment,
                $this->refundAmount,
                $this->refundReason ?: null,
                $this->refundRestock
            );

            $this->showRefundModal = false;
            $this->refundAmount = 0;
            $this->refundReason = '';
            $this->refundRestock = false;
            $this->order->refresh();
            $this->order->load('refunds');
            $this->dispatch('toast', type: 'success', message: __('Refund processed.'));
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function confirmPayment(OrderService $orderService): void
    {
        try {
            $orderService->confirmBankTransferPayment($this->order);
            $this->order->refresh();
            $this->order->load('payments');
            $this->dispatch('toast', type: 'success', message: __('Payment confirmed.'));
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.admin.orders.show');
    }
}
