<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentProvider;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public Order $order;

    public ?int $refundAmount = null;

    public string $trackingCompany = 'DHL';

    public string $trackingNumber = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load('lines.variant.product', 'payments', 'refunds', 'fulfillments.lines', 'customer');
    }

    public function fulfillAll(): void
    {
        $fulfillment = Fulfillment::create([
            'order_id' => $this->order->id,
            'status' => FulfillmentShipmentStatus::Shipped->value,
            'tracking_company' => $this->trackingCompany ?: null,
            'tracking_number' => $this->trackingNumber ?: null,
            'shipped_at' => now(),
            'created_at' => now(),
        ]);

        foreach ($this->order->lines as $line) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $line->id,
                'quantity' => $line->quantity,
            ]);
        }

        $this->order->update(['fulfillment_status' => FulfillmentStatus::Fulfilled->value]);
        $this->order->refresh()->load('fulfillments.lines');
        session()->flash('success', 'Order fulfilled.');
    }

    public function refund(OrderService $orderService, PaymentProvider $paymentProvider): void
    {
        $amount = (int) ($this->refundAmount ?? $this->order->total_amount);
        if ($amount <= 0) {
            return;
        }

        $payment = $this->order->payments()->latest()->first();
        $result = $paymentProvider->refund($this->order, $amount, $payment?->id);

        Refund::create([
            'order_id' => $this->order->id,
            'payment_id' => $payment?->id,
            'amount' => $amount,
            'reason' => 'admin_refund',
            'status' => $result->succeeded ? 'succeeded' : 'failed',
            'provider_refund_id' => $result->providerPaymentId,
            'created_at' => now(),
        ]);

        $totalRefunded = (int) $this->order->refunds()->where('status', 'succeeded')->sum('amount') + $amount;
        $financial = $totalRefunded >= $this->order->total_amount ? 'refunded' : 'partially_refunded';
        $this->order->update(['financial_status' => $financial]);
        $this->order->refresh()->load('refunds');

        session()->flash('success', 'Refund processed.');
    }

    public function cancel(OrderService $orderService): void
    {
        $orderService->cancel($this->order);
        $this->order->refresh();
        session()->flash('success', 'Order cancelled.');
    }

    public function render()
    {
        return view('livewire.admin.orders.show')->title($this->order->order_number);
    }
}
