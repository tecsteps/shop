<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public int $orderId;

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    /** @var array<int,int> */
    public array $fulfillmentLineQuantities = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    public function mount(int $order): void
    {
        $model = Order::query()->findOrFail($order);
        $this->authorize('view', $model);

        $this->orderId = (int) $model->getKey();
    }

    public function confirmPayment(InventoryService $inventory): void
    {
        $order = $this->resolveOrder();
        $this->authorize('update', $order);

        if ($order->payment_method !== PaymentMethod::BankTransfer || $order->financial_status !== FinancialStatus::Pending) {
            $this->addError('payment', 'Only pending bank transfer orders can be confirmed.');

            return;
        }

        foreach ($order->lines()->with('variant')->get() as $line) {
            if ($line->variant !== null) {
                $inventory->commit($line->variant, (int) $line->quantity);
            }
        }

        $order->financial_status = FinancialStatus::Paid;
        $order->status = OrderStatus::Paid;
        $order->save();

        OrderPaid::dispatch($order->refresh());

        session()->flash('status', 'Payment confirmed.');
    }

    public function openFulfillmentModal(): void
    {
        $order = $this->resolveOrder();

        $this->fulfillmentLineQuantities = [];
        foreach ($order->lines as $line) {
            $this->fulfillmentLineQuantities[$line->getKey()] = (int) $line->unfulfilledQuantity();
        }

        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(FulfillmentService $service): void
    {
        $order = $this->resolveOrder();
        $this->authorize('createFulfillment', $order);

        $lines = [];
        foreach ($this->fulfillmentLineQuantities as $lineId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $lines[] = ['order_line_id' => (int) $lineId, 'quantity' => $qty];
            }
        }

        if ($lines === []) {
            $this->addError('fulfillment', 'Select at least one line to fulfill.');

            return;
        }

        try {
            $service->create($order, $lines, [
                'tracking_company' => $this->trackingCompany !== '' ? $this->trackingCompany : null,
                'tracking_number' => $this->trackingNumber !== '' ? $this->trackingNumber : null,
                'tracking_url' => $this->trackingUrl !== '' ? $this->trackingUrl : null,
            ]);
        } catch (\Throwable $e) {
            $this->addError('fulfillment', $e->getMessage());

            return;
        }

        $this->reset(['showFulfillmentModal', 'trackingCompany', 'trackingNumber', 'trackingUrl']);
        session()->flash('status', 'Fulfillment created.');
    }

    public function markAsShipped(int $fulfillmentId, FulfillmentService $service): void
    {
        $order = $this->resolveOrder();
        $this->authorize('update', $order);

        $fulfillment = $order->fulfillments()->findOrFail($fulfillmentId);
        $service->markAsShipped($fulfillment);
        session()->flash('status', 'Fulfillment marked shipped.');
    }

    public function markAsDelivered(int $fulfillmentId, FulfillmentService $service): void
    {
        $order = $this->resolveOrder();
        $this->authorize('update', $order);

        $fulfillment = $order->fulfillments()->findOrFail($fulfillmentId);
        $service->markAsDelivered($fulfillment);
        session()->flash('status', 'Fulfillment marked delivered.');
    }

    public function openRefundModal(): void
    {
        $order = $this->resolveOrder();
        $this->refundAmount = (int) $order->remainingRefundable();
        $this->showRefundModal = true;
    }

    public function createRefund(RefundService $service): void
    {
        $order = $this->resolveOrder();
        $this->authorize('createRefund', $order);

        $payment = $order->payments()->orderByDesc('id')->first();

        if ($payment === null) {
            $this->addError('refund', 'No captured payment to refund.');

            return;
        }

        $amount = (int) $this->refundAmount;

        if ($amount <= 0) {
            $this->addError('refund', 'Enter a positive refund amount.');

            return;
        }

        try {
            $service->create($order, $payment, $amount, $this->refundReason !== '' ? $this->refundReason : null);
        } catch (\Throwable $e) {
            $this->addError('refund', $e->getMessage());

            return;
        }

        $this->reset(['showRefundModal', 'refundReason', 'refundAmount']);
        session()->flash('status', 'Refund issued.');
    }

    public function cancelOrder(OrderService $service): void
    {
        $order = $this->resolveOrder();
        $this->authorize('cancel', $order);

        try {
            $service->cancel($order);
        } catch (\Throwable $e) {
            $this->addError('cancel', $e->getMessage());

            return;
        }

        session()->flash('status', 'Order cancelled.');
    }

    public function render(): View
    {
        $order = Order::query()
            ->with(['lines.variant.product', 'payments', 'refunds', 'fulfillments.lines', 'customer'])
            ->findOrFail($this->orderId);

        return view('livewire.admin.orders.show', [
            'order' => $order,
            'canRefund' => auth()->user()?->can('createRefund', $order) ?? false,
            'canCancel' => auth()->user()?->can('cancel', $order) ?? false,
            'canFulfill' => auth()->user()?->can('createFulfillment', $order) ?? false,
            'shipmentPending' => FulfillmentShipmentStatus::Pending,
            'shipmentShipped' => FulfillmentShipmentStatus::Shipped,
            'fulfilled' => FulfillmentStatus::Fulfilled,
        ]);
    }

    protected function resolveOrder(): Order
    {
        return Order::query()->findOrFail($this->orderId);
    }
}
