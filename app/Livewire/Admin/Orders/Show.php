<?php

namespace App\Livewire\Admin\Orders;

use App\Actions\Orders\ConfirmBankTransferPayment;
use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Order detail with the full operational toolkit: a status timeline, line
 * items and money summary, payment info, and the fulfillment / refund actions.
 *
 * All mutating actions delegate to the domain services so guards (payment
 * before fulfillment, refundable-amount ceiling, inventory restock) stay
 * authoritative; the component only gathers input and authorizes the actor.
 */
#[Layout('livewire.admin.layout.app')]
class Show extends Component
{
    use BindsCurrentStore;

    public Order $order;

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    /** @var array<int, int> Quantity to fulfill, keyed by order_line_id. */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    /** @var array<int, int> Quantity to refund, keyed by order_line_id. */
    public array $refundLines = [];

    public ?string $refundAmount = null;

    public string $refundReason = '';

    public bool $refundRestock = true;

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order;
        $this->loadOrder();
    }

    private function loadOrder(): void
    {
        $this->order->load([
            'lines.variant.product.media',
            'lines.fulfillmentLines',
            'payments',
            'refunds',
            'fulfillments.lines.orderLine',
            'customer',
        ]);
    }

    public function getCanFulfillProperty(): bool
    {
        return in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true);
    }

    public function getCanRefundProperty(): bool
    {
        return in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true);
    }

    public function getCanConfirmPaymentProperty(): bool
    {
        return $this->order->payment_method === PaymentMethod::BankTransfer
            && $this->order->financial_status === FinancialStatus::Pending;
    }

    /**
     * The chronological event list rendered as the order timeline.
     *
     * @return array<int, array{title: string, at: \Illuminate\Support\Carbon|null}>
     */
    public function getTimelineProperty(): array
    {
        $events = [['title' => __('Order placed'), 'at' => $this->order->placed_at]];

        foreach ($this->order->payments as $payment) {
            if ($payment->status->value === 'captured') {
                $events[] = ['title' => __('Payment received'), 'at' => $payment->created_at];
            }
        }

        foreach ($this->order->fulfillments as $fulfillment) {
            $events[] = ['title' => __('Fulfillment created'), 'at' => $fulfillment->created_at];

            if ($fulfillment->shipped_at !== null) {
                $events[] = ['title' => __('Shipped'), 'at' => $fulfillment->shipped_at];
            }

            if ($fulfillment->delivered_at !== null) {
                $events[] = ['title' => __('Delivered'), 'at' => $fulfillment->delivered_at];
            }
        }

        foreach ($this->order->refunds as $refund) {
            $events[] = ['title' => __('Refund issued'), 'at' => $refund->created_at];
        }

        usort($events, fn (array $a, array $b): int => ($a['at']?->timestamp ?? 0) <=> ($b['at']?->timestamp ?? 0));

        return $events;
    }

    public function openFulfillmentModal(): void
    {
        $this->fulfillmentLines = [];

        foreach ($this->order->lines as $line) {
            $remaining = $line->quantity - $line->fulfilledQuantity();
            if ($remaining > 0) {
                $this->fulfillmentLines[$line->id] = $remaining;
            }
        }

        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(FulfillmentService $fulfillments): void
    {
        $this->authorize('createFulfillment', $this->order);

        $lines = collect($this->fulfillmentLines)
            ->map(fn ($qty): int => (int) $qty)
            ->filter(fn (int $qty): bool => $qty > 0)
            ->all();

        if ($lines === []) {
            $this->dispatch('toast', type: 'error', message: __('Select at least one line to fulfill.'));

            return;
        }

        try {
            $fulfillments->create($this->order, $lines, [
                'tracking_company' => $this->trackingCompany !== '' ? $this->trackingCompany : null,
                'tracking_number' => $this->trackingNumber !== '' ? $this->trackingNumber : null,
                'tracking_url' => $this->trackingUrl !== '' ? $this->trackingUrl : null,
            ]);

            $this->dispatch('toast', type: 'success', message: __('Fulfillment created'));
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->reset('trackingCompany', 'trackingNumber', 'trackingUrl');
        $this->showFulfillmentModal = false;
        $this->order->refresh();
        $this->loadOrder();
    }

    public function markAsShipped(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $fulfillment = $this->order->fulfillments->firstWhere('id', $fulfillmentId);

        if ($fulfillment === null) {
            return;
        }

        $this->authorize('createFulfillment', $this->order);
        $fulfillments->markAsShipped($fulfillment);

        $this->dispatch('toast', type: 'success', message: __('Marked as shipped'));
        $this->order->refresh();
        $this->loadOrder();
    }

    public function markAsDelivered(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $fulfillment = $this->order->fulfillments->firstWhere('id', $fulfillmentId);

        if ($fulfillment === null) {
            return;
        }

        $this->authorize('createFulfillment', $this->order);
        $fulfillments->markAsDelivered($fulfillment);

        $this->dispatch('toast', type: 'success', message: __('Marked as delivered'));
        $this->order->refresh();
        $this->loadOrder();
    }

    public function openRefundModal(): void
    {
        $this->refundLines = [];
        $this->refundAmount = null;
        $this->refundReason = '';
        $this->showRefundModal = true;
    }

    public function createRefund(RefundService $refunds): void
    {
        $this->authorize('createRefund', $this->order);

        $payment = $this->order->payments->firstWhere('status.value', 'captured')
            ?? $this->order->payments->first();

        if ($payment === null) {
            $this->dispatch('toast', type: 'error', message: __('No payment to refund against.'));

            return;
        }

        $lines = collect($this->refundLines)
            ->map(fn ($qty): int => (int) $qty)
            ->filter(fn (int $qty): bool => $qty > 0)
            ->all();

        $amount = $this->refundAmount !== null && $this->refundAmount !== ''
            ? (int) round(((float) $this->refundAmount) * 100)
            : null;

        try {
            $refunds->create(
                order: $this->order,
                payment: $payment,
                amount: $amount,
                reason: $this->refundReason !== '' ? $this->refundReason : null,
                restock: $this->refundRestock,
                lines: $lines,
            );

            $this->dispatch('toast', type: 'success', message: __('Refund issued'));
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->reset('refundAmount', 'refundReason', 'refundLines');
        $this->showRefundModal = false;
        $this->order->refresh();
        $this->loadOrder();
    }

    public function confirmPayment(ConfirmBankTransferPayment $action): void
    {
        $this->authorize('update', $this->order);

        try {
            $action->handle($this->order);
            $this->dispatch('toast', type: 'success', message: __('Payment confirmed'));
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->order->refresh();
        $this->loadOrder();
    }

    public function render()
    {
        return view('livewire.admin.orders.show');
    }
}
