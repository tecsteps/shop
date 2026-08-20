<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    public string $message = '';

    public int $refundAmount = 0;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['lines.variant.inventory', 'payments', 'fulfillments.lines']);
    }

    public function confirmPayment(OrderService $orders): void
    {
        $this->authorize('update', $this->order);
        $orders->confirmPayment($this->order);
        $this->message = 'Payment confirmed';
        $this->order = $this->order->refresh()->load(['lines.variant.inventory', 'payments', 'fulfillments.lines']);
    }

    public function fulfill(FulfillmentService $fulfillments): void
    {
        $this->authorize('createFulfillment', $this->order);
        $lines = $this->order->lines->map(fn ($line): array => ['order_line_id' => $line->id, 'quantity' => $line->quantity])->all();
        $fulfillments->create($this->order, $lines);
        $this->message = 'Fulfillment created';
        $this->order = $this->order->refresh()->load(['lines', 'payments', 'fulfillments.lines']);
    }

    public function refund(RefundService $refunds): void
    {
        $this->authorize('createRefund', $this->order);
        $payment = $this->order->payments->first();

        if ($payment === null) {
            $this->addError('refundAmount', 'No payment found.');

            return;
        }

        $refunds->create($this->order, $payment, $this->refundAmount ?: $payment->amount, 'Admin refund', true);
        $this->message = 'Refund processed';
        $this->order = $this->order->refresh()->load(['lines', 'payments', 'refunds']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.orders.show')->layout('layouts.admin');
    }
}
