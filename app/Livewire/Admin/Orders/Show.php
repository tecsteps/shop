<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public Order $order;

    public bool $showFulfillment = false;

    public bool $showRefund = false;

    /** @var array<int, int> */
    public array $fulfillLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public int $refundAmount = 0;

    public string $refundReason = '';

    public bool $refundRestock = false;

    public function mount(Order $order): void
    {
        $order->load(['lines.variant.product', 'payments', 'refunds', 'fulfillments.lines', 'customer']);
        $this->order = $order;
        $this->refundAmount = max(0, $order->total_amount - $order->totalRefunded());
    }

    public function openFulfillment(): void
    {
        $this->showFulfillment = true;
        $this->fulfillLines = [];
        foreach ($this->order->lines as $line) {
            $remaining = $line->quantity - $line->fulfilledQuantity();
            $this->fulfillLines[$line->id] = max(0, $remaining);
        }
    }

    public function closeFulfillment(): void
    {
        $this->showFulfillment = false;
    }

    public function fulfill(FulfillmentService $service): void
    {
        $lines = [];
        foreach ($this->fulfillLines as $lineId => $qty) {
            if ((int) $qty > 0) {
                $lines[] = ['order_line_id' => (int) $lineId, 'quantity' => (int) $qty];
            }
        }

        try {
            $service->create($this->order, $lines, [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber ?: null,
                'tracking_url' => $this->trackingUrl ?: null,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $this->order = $this->order->fresh(['lines.variant', 'fulfillments.lines', 'payments', 'refunds', 'customer']);
        $this->showFulfillment = false;
        session()->flash('success', 'Fulfillment created.');
    }

    public function openRefund(): void
    {
        $this->showRefund = true;
    }

    public function closeRefund(): void
    {
        $this->showRefund = false;
    }

    public function refund(RefundService $service): void
    {
        $payment = $this->order->payments()->latest('id')->first();
        if (! $payment) {
            session()->flash('error', 'No payment to refund.');

            return;
        }

        $service->create($this->order, $payment, (int) $this->refundAmount, $this->refundReason ?: null, $this->refundRestock);

        $this->order = $this->order->fresh(['lines.variant', 'fulfillments.lines', 'payments', 'refunds', 'customer']);
        $this->refundAmount = max(0, $this->order->total_amount - $this->order->totalRefunded());
        $this->showRefund = false;
        session()->flash('success', 'Refund processed.');
    }

    public function confirmBankTransfer(OrderService $service): void
    {
        $service->confirmBankTransferPayment($this->order);
        $this->order = $this->order->fresh(['lines.variant', 'fulfillments.lines', 'payments', 'refunds', 'customer']);
        session()->flash('success', 'Payment confirmed.');
    }

    public function cancelOrder(OrderService $service): void
    {
        $service->cancel($this->order, 'Cancelled by admin');
        $this->order = $this->order->fresh(['lines.variant', 'fulfillments.lines', 'payments', 'refunds', 'customer']);
        session()->flash('success', 'Order cancelled.');
    }

    public function render()
    {
        $canConfirmBankTransfer = $this->order->payment_method === PaymentMethod::BankTransfer
            && $this->order->financial_status === FinancialStatus::Pending;

        return view('livewire.admin.orders.show', [
            'canConfirmBankTransfer' => $canConfirmBankTransfer,
        ]);
    }
}
