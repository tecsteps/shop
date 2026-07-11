<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Admin\AdminComponent;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\PaymentService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Gate;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Show extends AdminComponent
{
    public int $orderId;

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    /** @var array<int, int> */
    public array $fulfillmentQuantities = [];

    public string $refundAmount = '';

    public string $refundReason = '';

    public bool $restock = false;

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);
        abort_unless($order->store_id === $this->currentStore()->getKey(), 404);
        $this->orderId = $order->getKey();
        foreach ($order->lines as $line) {
            $this->fulfillmentQuantities[$line->id] = $line->quantity;
        }
    }

    public function confirmPayment(PaymentService $service): void
    {
        $order = $this->order();
        Gate::authorize('update', $order);
        $service->confirmBankTransfer($order);
        $this->toast('Payment confirmed.');
    }

    public function createFulfillment(FulfillmentService $service): void
    {
        Gate::authorize('createFulfillment', $this->order());
        $validated = $this->validate([
            'trackingCompany' => ['nullable', 'string', 'max:100'], 'trackingNumber' => ['nullable', 'string', 'max:255'],
            'fulfillmentQuantities' => ['required', 'array'], 'fulfillmentQuantities.*' => ['integer', 'min:0'],
        ]);
        $lines = collect($validated['fulfillmentQuantities'])->filter(fn (int $quantity): bool => $quantity > 0)->all();
        $service->create($this->order(), $lines, ['tracking_company' => $validated['trackingCompany'], 'tracking_number' => $validated['trackingNumber']]);
        $this->showFulfillmentModal = false;
        $this->toast('Fulfillment created.');
    }

    public function markShipped(int $fulfillmentId, FulfillmentService $service): void
    {
        $fulfillment = $this->order()->fulfillments()->findOrFail($fulfillmentId);
        Gate::authorize('update', $fulfillment);
        $service->markAsShipped($fulfillment);
        $this->toast('Fulfillment marked as shipped.');
    }

    public function markDelivered(int $fulfillmentId, FulfillmentService $service): void
    {
        $fulfillment = $this->order()->fulfillments()->findOrFail($fulfillmentId);
        Gate::authorize('update', $fulfillment);
        $service->markAsDelivered($fulfillment);
        $this->toast('Fulfillment marked as delivered.');
    }

    public function processRefund(RefundService $service): void
    {
        Gate::authorize('createRefund', $this->order());
        $validated = $this->validate(['refundAmount' => ['required', 'numeric', 'min:0.01'], 'refundReason' => ['nullable', 'string', 'max:500'], 'restock' => ['boolean']]);
        $order = $this->order();
        $payment = $order->payments()->latest('id')->firstOrFail();
        $service->create($order, $payment, (int) round((float) $validated['refundAmount'] * 100), $validated['refundReason'], $validated['restock']);
        $this->showRefundModal = false;
        $this->toast('Refund processed.');
    }

    private function order(): Order
    {
        return Order::query()->where('store_id', $this->currentStore()->getKey())->with(['customer', 'lines', 'payments.refunds', 'refunds', 'fulfillments.lines'])->findOrFail($this->orderId);
    }

    public function render()
    {
        return view('livewire.admin.orders.show', ['order' => $this->order()]);
    }
}
