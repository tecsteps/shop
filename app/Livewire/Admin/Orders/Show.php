<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\RefundService;
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

    public string $error = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    public function openFulfillmentModal(): void
    {
        $this->trackingCompany = '';
        $this->trackingNumber = '';
        $this->error = '';
        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(FulfillmentService $fulfillmentService): void
    {
        $this->error = '';
        $this->order->load('lines');

        /** @var array<int, array{order_line_id: int, quantity: int}> $lines */
        $lines = $this->order->lines->map(fn ($line): array => [
            'order_line_id' => $line->id,
            'quantity' => $line->quantity,
        ])->toArray();

        /** @var array{tracking_company?: string, tracking_number?: string}|null $tracking */
        $tracking = null;
        if ($this->trackingCompany !== '' || $this->trackingNumber !== '') {
            $tracking = [];
            if ($this->trackingCompany !== '') {
                $tracking['tracking_company'] = $this->trackingCompany;
            }
            if ($this->trackingNumber !== '') {
                $tracking['tracking_number'] = $this->trackingNumber;
            }
        }

        try {
            $fulfillmentService->create($this->order, $lines, $tracking);
        } catch (\App\Exceptions\FulfillmentGuardException $e) {
            $this->error = $e->getMessage();

            return;
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->showFulfillmentModal = false;
        $this->order->refresh();
    }

    public function openRefundModal(): void
    {
        $this->refundAmount = $this->order->total_amount;
        $this->refundReason = '';
        $this->error = '';
        $this->showRefundModal = true;
    }

    public function processRefund(RefundService $refundService): void
    {
        $this->error = '';

        $this->validate([
            'refundAmount' => ['required', 'integer', 'min:1'],
            'refundReason' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = $this->order->payments()->first();

        if (! $payment) {
            $this->error = 'No payment found for this order.';

            return;
        }

        try {
            $refundService->create(
                $this->order,
                $payment,
                $this->refundAmount,
                $this->refundReason ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return;
        }

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
