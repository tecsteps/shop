<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public int $orderId;

    public string $refundAmount = '';

    public string $refundReason = '';

    public bool $restock = false;

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public function mount(int $order): void
    {
        $this->orderId = $order;
    }

    public function confirmPayment(OrderService $orders): void
    {
        $order = $this->order();
        Gate::authorize('update', $order);
        $orders->confirmBankTransfer($order);
        session()->flash('status', 'Payment confirmed.');
    }

    public function cancel(OrderService $orders): void
    {
        $order = $this->order();
        Gate::authorize('update', $order);
        $orders->cancelPending($order);
        session()->flash('status', 'Order cancelled.');
    }

    public function refund(RefundService $refunds): void
    {
        $order = $this->order();
        Gate::authorize('processRefund', $order);
        $this->validate(['refundAmount' => ['required', 'integer', 'min:1'], 'refundReason' => ['nullable', 'string', 'max:500']]);
        $refunds->refund($order, (int) $this->refundAmount, $this->refundReason ?: null, $this->restock);
        $this->reset(['refundAmount', 'refundReason', 'restock']);
        session()->flash('status', 'Refund processed.');
    }

    public function fulfill(FulfillmentService $fulfillments): void
    {
        $order = $this->order();
        Gate::authorize('createFulfillment', $order);
        $lineQuantities = $order->lines->mapWithKeys(fn ($line): array => [$line->id => $line->quantity - $line->fulfillmentLines()->sum('quantity')])->filter()->all();
        $fulfillments->create($order, $lineQuantities, [
            'tracking_company' => $this->trackingCompany ?: null,
            'tracking_number' => $this->trackingNumber ?: null,
        ]);
        session()->flash('status', 'Fulfillment created.');
    }

    public function render(): mixed
    {
        $order = $this->order()->load('customer', 'lines.variant', 'payments', 'refunds', 'fulfillments.lines.orderLine');
        Gate::authorize('view', $order);

        return view('livewire.admin.orders.show', ['order' => $order])->layout('layouts.admin');
    }

    private function order(): Order
    {
        return Order::query()->with('lines')->findOrFail($this->orderId);
    }
}
