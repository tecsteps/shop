<?php

namespace App\Livewire\Admin\Orders;

use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Livewire\Component;

class Show extends Component
{
    public int $orderId;

    public int $refundAmount = 0;

    public string $refundReason = '';

    public bool $refundRestock = false;

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public string $trackingCompany = '';

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function cancelOrder(): void
    {
        $order = Order::findOrFail($this->orderId);
        app(OrderService::class)->cancel($order);
        $this->dispatch('toast', type: 'success', message: 'Order cancelled.');
    }

    public function confirmBankTransfer(): void
    {
        $order = Order::findOrFail($this->orderId);
        app(OrderService::class)->confirmBankTransferPayment($order);
        $this->dispatch('toast', type: 'success', message: 'Payment confirmed.');
    }

    public function processRefund(): void
    {
        $order = Order::with('payments')->findOrFail($this->orderId);
        $payment = $order->payments()->where('status', 'captured')->first();

        if (! $payment) {
            $this->dispatch('toast', type: 'error', message: 'No captured payment found.');

            return;
        }

        try {
            app(RefundService::class)->refund($order, $payment, $this->refundAmount, $this->refundReason, $this->refundRestock);
            $this->refundAmount = 0;
            $this->refundReason = '';
            $this->refundRestock = false;
            $this->dispatch('toast', type: 'success', message: 'Refund processed.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function createFulfillment(): void
    {
        $order = Order::with('lines.fulfillmentLines')->findOrFail($this->orderId);

        $linesToFulfill = [];
        foreach ($order->lines as $line) {
            $fulfilled = $line->fulfillmentLines->sum('quantity');
            $remaining = $line->quantity - $fulfilled;
            if ($remaining > 0) {
                $linesToFulfill[$line->id] = $remaining;
            }
        }

        if (empty($linesToFulfill)) {
            $this->dispatch('toast', type: 'error', message: 'All lines are already fulfilled.');

            return;
        }

        try {
            $fulfillment = app(FulfillmentService::class)->createFulfillment(
                $order,
                $linesToFulfill,
                $this->trackingNumber ?: null,
                $this->trackingUrl ?: null,
                $this->trackingCompany ?: null,
            );
            $this->trackingNumber = '';
            $this->trackingUrl = '';
            $this->trackingCompany = '';
            $this->dispatch('toast', type: 'success', message: 'Fulfillment created.');
        } catch (FulfillmentGuardException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function render(): mixed
    {
        $order = Order::with(['lines', 'payments', 'refunds', 'fulfillments.lines', 'customer'])
            ->findOrFail($this->orderId);

        return view('livewire.admin.orders.show', [
            'order' => $order,
        ])->layout('layouts.admin.app', [
            'title' => "Order {$order->order_number}",
        ]);
    }
}
