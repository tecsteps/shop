<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    /** @var array<int, int|string> */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    public bool $refundRestock = false;

    public string $cancelReason = '';

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    public bool $showCancelModal = false;

    public bool $showShipModal = false;

    public ?int $shippingFulfillmentId = null;

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);

        $order->load(['lines.product.media', 'payments', 'refunds', 'fulfillments.lines.orderLine', 'customer']);

        $this->order = $order;
    }

    /**
     * Confirm a bank transfer payment was received (spec 05 §10.7).
     */
    public function confirmPayment(OrderService $orders): void
    {
        $this->authorize('update', $this->order);

        try {
            $orders->confirmBankTransferPayment($this->order);
        } catch (InvalidOrderTransitionException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->order->refresh();

        $this->dispatch('toast', type: 'success', message: 'Payment confirmed');
    }

    /**
     * Open the cancel-order modal (reason required).
     */
    public function openCancelModal(): void
    {
        $this->authorize('cancel', $this->order);

        $this->resetValidation();
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    /**
     * Cancel the order, releasing reserved inventory (spec 05 §11).
     */
    public function cancelOrder(OrderService $orders): void
    {
        $this->authorize('cancel', $this->order);

        $validated = $this->validate([
            'cancelReason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $orders->cancel($this->order, $validated['cancelReason']);
        } catch (InvalidOrderTransitionException $exception) {
            $this->showCancelModal = false;
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->showCancelModal = false;
        $this->cancelReason = '';
        $this->order->refresh();

        $this->dispatch('toast', type: 'success', message: 'Order cancelled');
    }

    /**
     * Open the fulfillment modal with all unfulfilled quantities preselected
     * (spec 03 §8).
     */
    public function openFulfillmentModal(): void
    {
        $this->authorize('createFulfillment', $this->order);

        $this->resetValidation();
        $this->resetFulfillmentForm();

        foreach ($this->unfulfilledQuantities() as $lineId => $quantity) {
            if ($quantity > 0) {
                $this->fulfillmentLines[$lineId] = $quantity;
            }
        }

        $this->showFulfillmentModal = true;
    }

    /**
     * Create a fulfillment for the selected lines (spec 05 §11.5). The
     * fulfillment guard is enforced by the service.
     */
    public function createFulfillment(FulfillmentService $fulfillments): void
    {
        $this->authorize('createFulfillment', $this->order);

        $this->validate($this->trackingRules());

        $lines = collect($this->fulfillmentLines)
            ->map(fn ($quantity): int => (int) $quantity)
            ->filter(fn (int $quantity): bool => $quantity > 0);

        if ($lines->isEmpty()) {
            $this->addError('fulfillmentLines', 'Select at least one item to fulfill.');

            return;
        }

        try {
            $fulfillments->create($this->order, $lines->all(), $this->trackingPayload());
        } catch (FulfillmentGuardException $exception) {
            $this->showFulfillmentModal = false;
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->showFulfillmentModal = false;
        $this->resetFulfillmentForm();
        $this->order->refresh();

        $this->dispatch('toast', type: 'success', message: 'Fulfillment created');
    }

    /**
     * Open the tracking form before marking a fulfillment as shipped.
     */
    public function openShipModal(int $fulfillmentId): void
    {
        $fulfillment = $this->findFulfillment($fulfillmentId);
        $this->authorize('update', $fulfillment);

        $this->resetValidation();
        $this->shippingFulfillmentId = $fulfillment->id;
        $this->trackingCompany = (string) ($fulfillment->tracking_company ?? '');
        $this->trackingNumber = (string) ($fulfillment->tracking_number ?? '');
        $this->trackingUrl = (string) ($fulfillment->tracking_url ?? '');
        $this->showShipModal = true;
    }

    /**
     * Transition a pending fulfillment to shipped with tracking data
     * (spec 05 §11.5).
     */
    public function markAsShipped(FulfillmentService $fulfillments): void
    {
        $fulfillment = $this->findFulfillment($this->shippingFulfillmentId);
        $this->authorize('update', $fulfillment);

        $this->validate($this->trackingRules());

        $fulfillments->markAsShipped($fulfillment, $this->trackingPayload());

        $this->showShipModal = false;
        $this->shippingFulfillmentId = null;
        $this->order->refresh();

        $this->dispatch('toast', type: 'success', message: 'Fulfillment marked as shipped');
    }

    /**
     * Transition a shipped fulfillment to delivered (spec 05 §11.5).
     */
    public function markAsDelivered(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $fulfillment = $this->findFulfillment($fulfillmentId);
        $this->authorize('update', $fulfillment);

        $fulfillments->markAsDelivered($fulfillment);

        $this->order->refresh();

        $this->dispatch('toast', type: 'success', message: 'Fulfillment marked as delivered');
    }

    /**
     * Open the refund modal with the full refundable amount preselected.
     */
    public function openRefundModal(): void
    {
        $this->authorize('createRefund', $this->order);

        $this->resetValidation();
        $this->refundAmount = $this->order->refundableAmount();
        $this->refundReason = '';
        $this->refundRestock = false;
        $this->showRefundModal = true;
    }

    /**
     * Create a refund for a custom amount (spec 05 §11.4).
     */
    public function createRefund(RefundService $refunds): void
    {
        $this->authorize('createRefund', $this->order);

        $refundable = $this->order->refundableAmount();

        $validated = $this->validate([
            'refundAmount' => ['required', 'integer', 'min:1', 'max:'.$refundable],
            'refundReason' => ['nullable', 'string', 'max:1000'],
            'refundRestock' => ['boolean'],
        ]);

        $payment = $this->order->payments()
            ->whereIn('status', [PaymentStatus::Captured->value, PaymentStatus::Refunded->value])
            ->latest('id')
            ->first();

        if ($payment === null) {
            $this->showRefundModal = false;
            $this->dispatch('toast', type: 'error', message: 'No refundable payment found for this order.');

            return;
        }

        $reason = trim((string) ($validated['refundReason'] ?? ''));

        try {
            $refunds->create(
                $this->order,
                $payment,
                (int) $validated['refundAmount'],
                $reason !== '' ? $reason : null,
                (bool) ($validated['refundRestock'] ?? false),
            );
        } catch (ValidationException $exception) {
            $this->addError('refundAmount', $exception->validator->errors()->first('amount'));

            return;
        }

        $this->showRefundModal = false;
        $this->order->refresh();

        $this->dispatch('toast', type: 'success', message: 'Refund issued');
    }

    /**
     * Whether the "Confirm payment" button applies (spec 05 §10.7).
     */
    public function canConfirmPayment(): bool
    {
        return $this->order->payment_method === PaymentMethod::BankTransfer
            && $this->order->financial_status === FinancialStatus::Pending;
    }

    /**
     * Whether the fulfillment guard blocks fulfillment creation: payment
     * must be confirmed (paid or partially refunded) first (spec 05 §11.5).
     */
    public function fulfillmentGuardBlocks(): bool
    {
        return ! in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true);
    }

    /**
     * Whether a refund can still be created for the order.
     */
    public function canRefund(): bool
    {
        return in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)
            && $this->order->refundableAmount() > 0;
    }

    /**
     * Whether the order can still be cancelled (not fulfilled/closed).
     */
    public function canCancel(): bool
    {
        return ! in_array($this->order->status, [OrderStatus::Fulfilled, OrderStatus::Cancelled, OrderStatus::Refunded], true)
            && $this->order->fulfillment_status !== FulfillmentOrderStatus::Fulfilled;
    }

    public function render(): View
    {
        return view('livewire.admin.orders.show', [
            'order' => $this->order,
            'unfulfilled' => $this->unfulfilledQuantities(),
            'refundableAmount' => $this->order->refundableAmount(),
            'timeline' => $this->timeline(),
        ])->layout('admin.layouts.app')->title('Order '.$this->order->order_number);
    }

    /**
     * Unfulfilled quantity per order line id.
     *
     * @return array<int, int>
     */
    private function unfulfilledQuantities(): array
    {
        $fulfilled = FulfillmentLine::query()
            ->whereIn('order_line_id', $this->order->lines->pluck('id'))
            ->selectRaw('order_line_id, SUM(quantity) as total')
            ->groupBy('order_line_id')
            ->pluck('total', 'order_line_id')
            ->map(fn ($total): int => (int) $total);

        $quantities = [];

        foreach ($this->order->lines as $line) {
            $quantities[$line->id] = max(0, $line->quantity - (int) ($fulfilled[$line->id] ?? 0));
        }

        return $quantities;
    }

    /**
     * Chronological list of order events for the timeline (spec 03 §8).
     *
     * @return list<array{title: string, time: Carbon|null}>
     */
    private function timeline(): array
    {
        $events = [
            ['title' => 'Order placed', 'time' => $this->order->placed_at],
        ];

        if (in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded, FinancialStatus::Refunded], true)) {
            $events[] = [
                'title' => 'Payment received',
                'time' => $this->order->payments->firstWhere('status', PaymentStatus::Captured)?->created_at ?? $this->order->updated_at,
            ];
        }

        foreach ($this->order->fulfillments as $fulfillment) {
            $events[] = ['title' => 'Fulfillment created', 'time' => $fulfillment->created_at];

            if ($fulfillment->shipped_at !== null) {
                $events[] = ['title' => 'Fulfillment shipped', 'time' => $fulfillment->shipped_at];
            }
        }

        foreach ($this->order->refunds as $refund) {
            $events[] = ['title' => 'Refund issued ('.Money::format($refund->amount, $this->order->currency).')', 'time' => $refund->created_at];
        }

        if ($this->order->status === OrderStatus::Cancelled) {
            $events[] = ['title' => 'Order cancelled', 'time' => $this->order->updated_at];
        }

        usort($events, fn (array $a, array $b): int => ($a['time'] ?? $this->order->placed_at) <=> ($b['time'] ?? $this->order->placed_at));

        return $events;
    }

    /**
     * Find a fulfillment belonging to this order.
     */
    private function findFulfillment(?int $fulfillmentId): Fulfillment
    {
        return $this->order->fulfillments()->findOrFail($fulfillmentId);
    }

    /**
     * @return array<string, mixed>
     */
    private function trackingRules(): array
    {
        return [
            'trackingCompany' => ['nullable', 'string', 'max:255'],
            'trackingNumber' => ['nullable', 'string', 'max:255'],
            'trackingUrl' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * Tracking payload for the fulfillment service: empty strings become
     * null.
     *
     * @return array{tracking_company: string|null, tracking_number: string|null, tracking_url: string|null}
     */
    private function trackingPayload(): array
    {
        return [
            'tracking_company' => trim($this->trackingCompany) !== '' ? trim($this->trackingCompany) : null,
            'tracking_number' => trim($this->trackingNumber) !== '' ? trim($this->trackingNumber) : null,
            'tracking_url' => trim($this->trackingUrl) !== '' ? trim($this->trackingUrl) : null,
        ];
    }

    private function resetFulfillmentForm(): void
    {
        $this->fulfillmentLines = [];
        $this->trackingCompany = '';
        $this->trackingNumber = '';
        $this->trackingUrl = '';
    }
}
