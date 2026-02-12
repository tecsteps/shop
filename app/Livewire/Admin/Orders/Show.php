<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Order Details')]
class Show extends Component
{
    public Order $order;

    public bool $showFulfillModal = false;

    public bool $showRefundModal = false;

    public bool $showCancelModal = false;

    public string $cancelReason = '';

    /** @var array<int, int> */
    public array $fulfillLines = [];

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public string $carrier = '';

    public string $refundAmount = '';

    public string $refundReason = '';

    /** @var array<int, int> */
    public array $refundLines = [];

    public function mount(Order $order): void
    {
        $store = app('current_store');

        abort_unless($order->store_id === $store->id, 404);

        $this->order = $order;
    }

    public function openFulfillModal(): void
    {
        $this->order->load('lines', 'fulfillments.lines');

        $this->fulfillLines = [];

        foreach ($this->getUnfulfilledLines() as $line) {
            $this->fulfillLines[$line['id']] = $line['unfulfilled'];
        }

        $this->trackingNumber = '';
        $this->trackingUrl = '';
        $this->carrier = '';
        $this->showFulfillModal = true;
    }

    public function closeFulfillModal(): void
    {
        $this->showFulfillModal = false;
    }

    public function createFulfillment(FulfillmentService $fulfillmentService): void
    {
        $lines = collect($this->fulfillLines)
            ->filter(fn ($qty) => $qty > 0)
            ->all();

        if (empty($lines)) {
            $this->dispatch('toast', type: 'error', message: 'Please select at least one line to fulfill.');

            return;
        }

        try {
            $fulfillmentService->createFulfillment(
                $this->order,
                $lines,
                array_filter([
                    'tracking_number' => $this->trackingNumber ?: null,
                    'tracking_url' => $this->trackingUrl ?: null,
                    'tracking_company' => $this->carrier ?: null,
                ])
            );

            $this->dispatch('toast', type: 'success', message: 'Fulfillment created successfully.');
            $this->showFulfillModal = false;
        } catch (FulfillmentGuardException|InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function openRefundModal(): void
    {
        $this->order->load('lines', 'refunds');

        $this->refundAmount = '';
        $this->refundReason = '';
        $this->refundLines = [];

        foreach ($this->order->lines as $line) {
            $this->refundLines[$line->id] = 0;
        }

        $this->showRefundModal = true;
    }

    public function closeRefundModal(): void
    {
        $this->showRefundModal = false;
    }

    public function processRefund(RefundService $refundService): void
    {
        $request = [];

        if ($this->refundAmount !== '') {
            $request['amount'] = (int) round((float) $this->refundAmount * 100);
        }

        $selectedLines = collect($this->refundLines)
            ->filter(fn ($qty) => $qty > 0)
            ->all();

        if (! empty($selectedLines)) {
            $request['lines'] = $selectedLines;
        }

        if ($this->refundReason !== '') {
            $request['reason'] = $this->refundReason;
        }

        try {
            $refundService->processRefund($this->order, $request);

            $this->dispatch('toast', type: 'success', message: 'Refund processed successfully.');
            $this->showRefundModal = false;
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function openCancelModal(): void
    {
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function cancelOrder(OrderService $orderService): void
    {
        if ($this->order->status === OrderStatus::Cancelled) {
            $this->dispatch('toast', type: 'error', message: 'This order is already cancelled.');

            return;
        }

        $orderService->cancel($this->order, $this->cancelReason ?: null);

        $this->dispatch('toast', type: 'success', message: 'Order cancelled successfully.');
        $this->showCancelModal = false;
    }

    /**
     * @return array<int, array{id: int, title: string, variant_title: string|null, quantity: int, fulfilled: int, unfulfilled: int}>
     */
    public function getUnfulfilledLines(): array
    {
        $this->order->load('lines', 'fulfillments.lines');

        $unfulfilledLines = [];

        foreach ($this->order->lines as $line) {
            $fulfilledQty = $this->order->fulfillments->flatMap->lines
                ->where('order_line_id', $line->id)
                ->sum('quantity');

            $unfulfilled = $line->quantity - $fulfilledQty;

            if ($unfulfilled > 0) {
                $unfulfilledLines[] = [
                    'id' => $line->id,
                    'title' => $line->title_snapshot,
                    'variant_title' => $line->variant_title_snapshot,
                    'quantity' => $line->quantity,
                    'fulfilled' => $fulfilledQty,
                    'unfulfilled' => $unfulfilled,
                ];
            }
        }

        return $unfulfilledLines;
    }

    public function render(): View
    {
        $this->order->load([
            'customer',
            'lines',
            'payments',
            'refunds',
            'fulfillments.lines.orderLine',
        ]);

        $isFullyFulfilled = $this->order->fulfillment_status === FulfillmentStatus::Fulfilled;
        $isCancelled = $this->order->status === OrderStatus::Cancelled;

        return view('livewire.admin.orders.show', [
            'isFullyFulfilled' => $isFullyFulfilled,
            'isCancelled' => $isCancelled,
        ]);
    }
}
