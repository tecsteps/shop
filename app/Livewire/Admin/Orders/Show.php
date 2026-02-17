<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Order Detail')]
class Show extends Component
{
    public Order $order;

    /** @var array<int, int> */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    /** @var array<int, int> */
    public array $refundLines = [];

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order->load(['lines', 'payments', 'fulfillments.lines', 'customer']);

        // Initialize fulfillment lines with max unfulfilled quantities
        foreach ($this->order->lines as $line) {
            $fulfilledQty = $line->fulfillmentLines->sum('quantity');
            $remaining = $line->quantity - $fulfilledQty;
            $this->fulfillmentLines[$line->id] = $remaining > 0 ? $remaining : 0;
        }
    }

    public function confirmPayment(): void
    {
        $this->authorize('update', $this->order);

        $orderService = app(OrderService::class);
        $orderService->confirmBankTransferPayment($this->order);

        $this->order->refresh()->load(['lines', 'payments', 'fulfillments.lines', 'customer']);
        $this->dispatch('toast', type: 'success', message: 'Payment confirmed.');
    }

    public function createFulfillment(): void
    {
        $this->authorize('createFulfillment', $this->order);

        /** @var array<int, int> $linesToFulfill */
        $linesToFulfill = collect($this->fulfillmentLines)->filter(fn ($qty) => $qty > 0)->all();

        if (empty($linesToFulfill)) {
            $this->dispatch('toast', type: 'error', message: 'No items selected for fulfillment.');

            return;
        }

        $fulfillmentService = app(FulfillmentService::class);
        $tracking = array_filter([
            'tracking_company' => $this->trackingCompany ?: null,
            'tracking_number' => $this->trackingNumber ?: null,
            'tracking_url' => $this->trackingUrl ?: null,
        ]);

        $fulfillmentService->create($this->order, $linesToFulfill, $tracking ?: null);

        $this->trackingCompany = '';
        $this->trackingNumber = '';
        $this->trackingUrl = '';

        $this->order->refresh()->load(['lines', 'fulfillments.lines', 'payments', 'customer']);
        $this->dispatch('toast', type: 'success', message: 'Fulfillment created.');
        $this->modal('create-fulfillment')->close();
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $this->authorize('update', $this->order);

        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        $fulfillmentService = app(FulfillmentService::class);
        $fulfillmentService->markAsShipped($fulfillment);

        $this->order->refresh()->load(['fulfillments.lines']);
        $this->dispatch('toast', type: 'success', message: 'Marked as shipped.');
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $this->authorize('update', $this->order);

        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        $fulfillmentService = app(FulfillmentService::class);
        $fulfillmentService->markAsDelivered($fulfillment);

        $this->order->refresh()->load(['fulfillments.lines']);
        $this->dispatch('toast', type: 'success', message: 'Marked as delivered.');
    }

    public function createRefund(): void
    {
        $this->authorize('createRefund', $this->order);

        $amount = $this->refundAmount ? (int) ($this->refundAmount * 100) : 0;

        if ($amount <= 0) {
            // Calculate from selected lines
            foreach ($this->refundLines as $lineId => $qty) {
                $line = $this->order->lines->firstWhere('id', $lineId);
                if ($line && $qty > 0) {
                    $amount += $line->unit_price_amount * $qty;
                }
            }
        }

        if ($amount <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Please enter a refund amount or select items.');

            return;
        }

        $payment = $this->order->payments()->where('status', PaymentStatus::Captured)->first();

        if (! $payment) {
            $this->dispatch('toast', type: 'error', message: 'No captured payment found.');

            return;
        }

        $refundService = app(RefundService::class);
        $refundService->create(
            $this->order,
            $payment,
            $amount,
            $this->refundReason ?: null,
        );

        $this->refundAmount = null;
        $this->refundReason = '';
        $this->refundLines = [];

        $this->order->refresh()->load(['lines', 'payments', 'refunds', 'fulfillments.lines', 'customer']);
        $this->dispatch('toast', type: 'success', message: 'Refund created.');
        $this->modal('create-refund')->close();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.orders.show');
    }
}
