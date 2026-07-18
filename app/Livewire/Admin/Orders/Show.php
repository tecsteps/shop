<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Order details')]
class Show extends Component
{
    public Order $order;

    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    public array $refundLines = [];

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);
        $this->order = $order;
        $this->reloadOrder();
        foreach ($this->order->lines as $line) {
            $alreadyFulfilled = (int) $line->fulfillmentLines()->sum('quantity');
            $this->fulfillmentLines[$line->id] = max(0, $line->quantity - $alreadyFulfilled);
            $this->refundLines[$line->id] = 0;
        }
    }

    public function confirmPayment(OrderService $service): void
    {
        Gate::authorize('update', $this->order);
        $this->order = $service->confirmBankTransferPayment($this->order);
        $this->reloadOrder();
        $this->dispatch('toast', type: 'success', message: 'Payment confirmed.');
    }

    public function createFulfillment(FulfillmentService $service): void
    {
        Gate::authorize('fulfill', $this->order);
        $this->validate(['trackingCompany' => ['nullable', 'string', 'max:255'], 'trackingNumber' => ['nullable', 'string', 'max:255'], 'trackingUrl' => ['nullable', 'url', 'max:2048'], 'fulfillmentLines' => ['array']]);
        $lines = array_filter(array_map('intval', $this->fulfillmentLines), fn (int $quantity): bool => $quantity > 0);
        $service->create($this->order, $lines, ['tracking_company' => $this->trackingCompany ?: null, 'tracking_number' => $this->trackingNumber ?: null, 'tracking_url' => $this->trackingUrl ?: null]);
        $this->reloadOrder();
        $this->dispatch('toast', type: 'success', message: 'Fulfillment created.');
    }

    public function markAsShipped(int $fulfillmentId, FulfillmentService $service): void
    {
        Gate::authorize('fulfill', $this->order);
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        $service->markShipped($fulfillment);
        $this->reloadOrder();
    }

    public function markAsDelivered(int $fulfillmentId, FulfillmentService $service): void
    {
        Gate::authorize('fulfill', $this->order);
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        $service->markDelivered($fulfillment);
        $this->reloadOrder();
    }

    public function createRefund(RefundService $service): void
    {
        Gate::authorize('refund', $this->order);
        $this->validate(['refundAmount' => ['nullable', 'integer', 'min:1'], 'refundReason' => ['nullable', 'string', 'max:1000'], 'refundLines' => ['array']]);
        $lines = array_filter(array_map('intval', $this->refundLines), fn (int $quantity): bool => $quantity > 0);
        $request = ['lines' => $lines, 'reason' => $this->refundReason ?: null, 'restock' => $lines !== []];
        if ($this->refundAmount !== null) {
            $request['amount'] = $this->refundAmount;
        }
        $service->process($this->order, $request);
        $this->reloadOrder();
        $this->dispatch('toast', type: 'success', message: 'Refund issued.');
    }

    private function reloadOrder(): void
    {
        $this->order->refresh()->load(['customer', 'lines.variant.product', 'payments', 'refunds', 'fulfillments.lines']);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.show');
    }
}
