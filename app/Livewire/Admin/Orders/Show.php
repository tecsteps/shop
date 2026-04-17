<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Show extends Component
{
    public Order $order;

    /** @var array<int, array{line_id: int, quantity: int, selected: bool}> */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    /** @var array<int, array{line_id: int, quantity: int, selected: bool}> */
    public array $refundLines = [];

    public function mount(Order $order): void
    {
        $this->order = $order->load(['lines', 'payments', 'fulfillments.lines', 'customer']);

        $this->initFulfillmentLines();
        $this->initRefundLines();
    }

    public function confirmPayment(): void
    {
        app(OrderService::class)->confirmBankTransferPayment($this->order);

        $this->order->refresh();
        $this->dispatch('toast', type: 'success', message: 'Payment confirmed.');
    }

    public function openFulfillmentModal(): void
    {
        $this->initFulfillmentLines();
        $this->modal('create-fulfillment')->show();
    }

    public function createFulfillment(): void
    {
        $linesToFulfill = [];
        foreach ($this->fulfillmentLines as $fl) {
            if ($fl['selected'] && $fl['quantity'] > 0) {
                $linesToFulfill[$fl['line_id']] = $fl['quantity'];
            }
        }

        if (empty($linesToFulfill)) {
            $this->dispatch('toast', type: 'error', message: 'Please select lines to fulfill.');

            return;
        }

        $tracking = null;
        if ($this->trackingNumber) {
            $tracking = [
                'tracking_company' => $this->trackingCompany,
                'tracking_number' => $this->trackingNumber,
                'tracking_url' => $this->trackingUrl,
            ];
        }

        try {
            app(FulfillmentService::class)->create($this->order, $linesToFulfill, $tracking);
            $this->order->refresh();
            $this->modal('create-fulfillment')->close();
            $this->dispatch('toast', type: 'success', message: 'Fulfillment created.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);

        $tracking = null;
        if ($this->trackingNumber) {
            $tracking = [
                'tracking_company' => $this->trackingCompany,
                'tracking_number' => $this->trackingNumber,
                'tracking_url' => $this->trackingUrl,
            ];
        }

        app(FulfillmentService::class)->markAsShipped($fulfillment, $tracking);
        $this->order->refresh();
        $this->dispatch('toast', type: 'success', message: 'Marked as shipped.');
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        app(FulfillmentService::class)->markAsDelivered($fulfillment);
        $this->order->refresh();
        $this->dispatch('toast', type: 'success', message: 'Marked as delivered.');
    }

    public function openRefundModal(): void
    {
        $this->initRefundLines();
        $this->refundAmount = null;
        $this->refundReason = '';
        $this->modal('create-refund')->show();
    }

    public function createRefund(): void
    {
        $payment = $this->order->payments()
            ->where('status', PaymentStatus::Captured)
            ->first();

        if (! $payment) {
            $this->dispatch('toast', type: 'error', message: 'No captured payment found.');

            return;
        }

        $amount = $this->refundAmount
            ? (int) round($this->refundAmount * 100)
            : null;

        if (! $amount) {
            // Calculate from selected lines
            $amount = 0;
            foreach ($this->refundLines as $rl) {
                if ($rl['selected'] && $rl['quantity'] > 0) {
                    $line = $this->order->lines->firstWhere('id', $rl['line_id']);
                    if ($line) {
                        $amount += $line->unit_price_amount * $rl['quantity'];
                    }
                }
            }
        }

        if ($amount <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Please specify a refund amount or select lines.');

            return;
        }

        try {
            app(RefundService::class)->create(
                $this->order,
                $payment,
                $amount,
                $this->refundReason ?: null,
            );
            $this->order->refresh();
            $this->modal('create-refund')->close();
            $this->dispatch('toast', type: 'success', message: 'Refund issued.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    private function initFulfillmentLines(): void
    {
        $this->fulfillmentLines = $this->order->lines->map(fn ($line) => [
            'line_id' => $line->id,
            'quantity' => $line->quantity,
            'selected' => true,
        ])->toArray();
    }

    private function initRefundLines(): void
    {
        $this->refundLines = $this->order->lines->map(fn ($line) => [
            'line_id' => $line->id,
            'quantity' => 0,
            'selected' => false,
        ])->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.orders.show');
    }
}
