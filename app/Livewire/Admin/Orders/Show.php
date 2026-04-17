<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use DomainException;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public Order $order;

    public bool $showFulfillModal = false;

    public bool $showRefundModal = false;

    /** @var array<int, int> */
    public array $fulfillLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public int $refundAmount = 0;

    public string $refundReason = '';

    public bool $refundRestock = false;

    public string $cancelReason = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load(['lines', 'customer', 'payments', 'refunds', 'fulfillments.lines']);
        $this->refundAmount = (int) $order->refundableAmount();

        foreach ($this->order->lines as $line) {
            $this->fulfillLines[$line->id] = (int) $line->quantity;
        }
    }

    public function openFulfillModal(): void
    {
        $this->showFulfillModal = true;
    }

    public function openRefundModal(): void
    {
        $this->showRefundModal = true;
    }

    public function createFulfillment(FulfillmentService $service): void
    {
        try {
            $lines = array_filter(array_map('intval', $this->fulfillLines), fn (int $qty): bool => $qty > 0);

            if ($lines === []) {
                $this->addError('fulfill', 'Select at least one line.');

                return;
            }

            $service->create($this->order, $lines, [
                'company' => $this->trackingCompany !== '' ? $this->trackingCompany : null,
                'number' => $this->trackingNumber !== '' ? $this->trackingNumber : null,
                'url' => $this->trackingUrl !== '' ? $this->trackingUrl : null,
            ]);

            $this->showFulfillModal = false;
            $this->order->refresh()->load(['lines', 'fulfillments.lines']);
            session()->flash('status', 'Fulfillment created.');
        } catch (\Throwable $exception) {
            $this->addError('fulfill', $exception->getMessage());
        }
    }

    public function markShipped(int $fulfillmentId, FulfillmentService $service): void
    {
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        $service->markAsShipped($fulfillment);
        $this->order->refresh()->load('fulfillments.lines');
    }

    public function markDelivered(int $fulfillmentId, FulfillmentService $service): void
    {
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        $service->markAsDelivered($fulfillment);
        $this->order->refresh()->load('fulfillments.lines');
    }

    public function createRefund(RefundService $service): void
    {
        $payment = $this->order->payments()->latest('id')->first();

        if ($payment === null) {
            $this->addError('refund', 'No payment to refund.');

            return;
        }

        try {
            $service->create(
                $this->order,
                $payment,
                $this->refundAmount,
                $this->refundReason !== '' ? $this->refundReason : null,
                $this->refundRestock,
            );

            $this->showRefundModal = false;
            $this->order->refresh()->load(['lines', 'payments', 'refunds']);
            session()->flash('status', 'Refund processed.');
        } catch (InvalidArgumentException $exception) {
            $this->addError('refund', $exception->getMessage());
        }
    }

    public function confirmBankTransfer(OrderService $service): void
    {
        try {
            $service->confirmBankTransferPayment($this->order);
            $this->order->refresh();
            session()->flash('status', 'Payment confirmed.');
        } catch (DomainException $exception) {
            $this->addError('order', $exception->getMessage());
        }
    }

    public function cancelOrder(OrderService $service): void
    {
        try {
            $service->cancel($this->order, $this->cancelReason !== '' ? $this->cancelReason : null);
            $this->order->refresh();
            session()->flash('status', 'Order cancelled.');
        } catch (DomainException $exception) {
            $this->addError('order', $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.admin.orders.show');
    }
}
