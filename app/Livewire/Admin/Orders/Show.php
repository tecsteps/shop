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

    public string $trackingUrl = '';

    /** @var array<int, int|string> */
    public array $fulfillmentLines = [];

    public function mount(int $order): void
    {
        $this->orderId = $order;
        $this->setRemainingFulfillmentQuantities($this->order());
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

        $validated = $this->validate([
            'fulfillmentLines' => ['required', 'array', 'min:1'],
            'fulfillmentLines.*' => ['required', 'integer', 'min:0'],
            'trackingCompany' => ['nullable', 'string', 'max:255'],
            'trackingNumber' => ['nullable', 'string', 'max:255'],
            'trackingUrl' => ['nullable', 'url', 'regex:/\Ahttps?:\/\/\S+\z/i', 'max:255'],
        ]);
        $lineQuantities = collect($validated['fulfillmentLines'])
            ->map(fn (int|string $quantity): int => (int) $quantity)
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();

        if ($lineQuantities === []) {
            $this->addError('fulfillmentLines', 'Select at least one item to fulfill.');

            return;
        }

        $fulfillments->create($order, $lineQuantities, [
            'tracking_company' => $this->trackingCompany ?: null,
            'tracking_number' => $this->trackingNumber ?: null,
            'tracking_url' => $this->trackingUrl ?: null,
        ]);
        $this->reset(['trackingCompany', 'trackingNumber', 'trackingUrl']);
        $this->setRemainingFulfillmentQuantities($this->order());
        session()->flash('status', 'Fulfillment created.');
    }

    public function markAsShipped(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $order = $this->order();
        Gate::authorize('update', $order);
        $fulfillment = $order->fulfillments()->findOrFail($fulfillmentId);

        $fulfillments->markShipped($fulfillment);
        session()->flash('status', 'Fulfillment marked as shipped.');
    }

    public function markAsDelivered(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $order = $this->order();
        Gate::authorize('update', $order);
        $fulfillment = $order->fulfillments()->findOrFail($fulfillmentId);

        $fulfillments->markDelivered($fulfillment);
        session()->flash('status', 'Fulfillment marked as delivered.');
    }

    public function render(): mixed
    {
        $order = $this->order()->load('customer', 'lines.variant', 'lines.fulfillmentLines', 'payments', 'refunds', 'fulfillments.lines.orderLine');
        Gate::authorize('view', $order);

        return view('livewire.admin.orders.show', ['order' => $order])->layout('layouts.admin');
    }

    private function order(): Order
    {
        return Order::query()->with('lines.fulfillmentLines')->findOrFail($this->orderId);
    }

    private function setRemainingFulfillmentQuantities(Order $order): void
    {
        $this->fulfillmentLines = $order->lines->mapWithKeys(fn ($line): array => [
            $line->id => max(0, $line->quantity - (int) $line->fulfillmentLines->sum('quantity')),
        ])->all();
    }
}
