<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    /** @var array<int, int> */
    public array $fulfillmentLines = [];

    /** @var array<int, bool> */
    public array $selectedFulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    /** @var array<int, int> */
    public array $refundLines = [];

    /** @var array<int, bool> */
    public array $selectedRefundLines = [];

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    public string $message = '';

    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->authorize('view', $this->order);
        $this->loadOrder();
    }

    public function openFulfillmentModal(): void
    {
        $this->authorize('createFulfillment', $this->order);
        $this->resetValidation();
        $this->fulfillmentLines = [];
        $this->selectedFulfillmentLines = [];

        foreach ($this->order->lines as $line) {
            $remaining = max(0, $line->quantity - $this->fulfilledQuantity($line->id));

            if ($remaining > 0) {
                $this->fulfillmentLines[$line->id] = $remaining;
                $this->selectedFulfillmentLines[$line->id] = true;
            }
        }

        $this->showFulfillmentModal = true;
    }

    public function openRefundModal(): void
    {
        $this->authorize('createRefund', $this->order);
        $this->resetValidation();
        $this->refundAmount = null;
        $this->refundReason = '';
        $this->refundLines = [];
        $this->selectedRefundLines = [];
        foreach ($this->order->lines as $line) {
            $this->refundLines[$line->id] = $line->quantity;
            $this->selectedRefundLines[$line->id] = false;
        }
        $this->showRefundModal = true;
    }

    public function confirmPayment(OrderService $orders): void
    {
        $this->authorize('update', $this->order);

        try {
            $orders->confirmPayment($this->order);
            $this->message = 'Payment confirmed.';
            $this->loadOrder();
        } catch (\Throwable $exception) {
            $this->addError('payment', $exception->getMessage());
        }
    }

    public function createFulfillment(FulfillmentService $fulfillments): void
    {
        $this->authorize('createFulfillment', $this->order);

        $this->validate([
            'trackingCompany' => ['nullable', 'string', 'max:100'],
            'trackingNumber' => ['nullable', 'string', 'max:100'],
            'trackingUrl' => ['nullable', 'url', 'max:500'],
            'fulfillmentLines' => ['array'],
            'fulfillmentLines.*' => ['integer', 'min:0'],
        ]);

        $lines = collect($this->fulfillmentLines)
            ->mapWithKeys(fn (int|string $quantity, int|string $lineId): array => [(int) $lineId => (int) $quantity])
            ->filter(fn (int $quantity, int $lineId): bool => (bool) ($this->selectedFulfillmentLines[$lineId] ?? false))
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->map(fn (int $quantity, int $lineId): array => ['order_line_id' => $lineId, 'quantity' => $quantity])
            ->values()
            ->all();

        if ($lines === []) {
            $this->addError('fulfillmentLines', 'Select at least one item to fulfill.');

            return;
        }

        try {
            $fulfillments->create($this->order, $lines, array_filter([
                'tracking_company' => $this->trackingCompany,
                'tracking_number' => $this->trackingNumber,
                'tracking_url' => $this->trackingUrl,
            ]));
            $this->showFulfillmentModal = false;
            $this->message = 'Fulfillment created.';
            $this->loadOrder();
        } catch (\Throwable $exception) {
            $this->addError('fulfillmentLines', $exception->getMessage());
        }
    }

    public function markAsShipped(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $this->authorize('createFulfillment', $this->order);
        $fulfillment = $this->order->fulfillments->firstWhere('id', $fulfillmentId);

        abort_if($fulfillment === null, 404);

        try {
            $fulfillments->markAsShipped($fulfillment);
            $this->message = 'Fulfillment marked as shipped.';
            $this->loadOrder();
        } catch (\Throwable $exception) {
            $this->addError('fulfillment', $exception->getMessage());
        }
    }

    public function markAsDelivered(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $this->authorize('createFulfillment', $this->order);
        $fulfillment = $this->order->fulfillments->firstWhere('id', $fulfillmentId);

        abort_if($fulfillment === null, 404);

        try {
            $fulfillments->markAsDelivered($fulfillment);
            $this->message = 'Fulfillment marked as delivered.';
            $this->loadOrder();
        } catch (\Throwable $exception) {
            $this->addError('fulfillment', $exception->getMessage());
        }
    }

    public function createRefund(RefundService $refunds): void
    {
        $this->authorize('createRefund', $this->order);
        $this->validate([
            'refundAmount' => ['nullable', 'integer', 'min:1'],
            'refundReason' => ['nullable', 'string', 'max:1000'],
            'refundLines' => ['array'],
            'refundLines.*' => ['integer', 'min:0'],
        ]);

        $lines = collect($this->refundLines)
            ->mapWithKeys(fn (int|string $quantity, int|string $lineId): array => [(int) $lineId => (int) $quantity])
            ->filter(fn (int $quantity, int $lineId): bool => (bool) ($this->selectedRefundLines[$lineId] ?? false))
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();

        if ($lines === [] && $this->refundAmount === null) {
            $this->addError('refundAmount', 'Select items or enter a refund amount in cents.');

            return;
        }

        $payment = $this->order->payments->first();

        if ($payment === null) {
            $this->addError('refundAmount', 'No payment is available for this order.');

            return;
        }

        try {
            $refunds->create($this->order, $payment, $lines !== [] ? $lines : $this->refundAmount, $this->refundReason ?: null, true);
            $this->showRefundModal = false;
            $this->message = 'Refund processed.';
            $this->loadOrder();
        } catch (\Throwable $exception) {
            $this->addError('refundAmount', $exception->getMessage());
        }
    }

    public function render(): mixed
    {
        return view('livewire.admin.orders.show')->layout('layouts.admin');
    }

    private function loadOrder(): void
    {
        $this->order = $this->order->refresh()->load([
            'customer',
            'lines.variant.product',
            'payments',
            'refunds',
            'fulfillments.lines.orderLine',
        ]);
    }

    private function fulfilledQuantity(int $lineId): int
    {
        return (int) $this->order->fulfillments
            ->whereIn('status', ['shipped', 'delivered'])
            ->flatMap->lines
            ->where('order_line_id', $lineId)
            ->sum('quantity');
    }
}
