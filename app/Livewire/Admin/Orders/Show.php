<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\FulfillmentGuardException;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Order $order;

    /** @var array<int, int> */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    public bool $refundRestock = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'lines.product.media',
            'lines.variant',
            'lines.fulfillmentLines',
            'payments',
            'fulfillments.lines.orderLine',
            'refunds',
            'customer',
        ]);

        $this->initFulfillmentLines();
    }

    public function confirmPayment(): void
    {
        $this->authorize('update', $this->order);

        try {
            app(OrderService::class)->confirmBankTransferPayment($this->order);
            $this->order->refresh();
            $this->dispatch('toast', type: 'success', message: 'Payment confirmed successfully.');
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function openFulfillmentModal(): void
    {
        $this->initFulfillmentLines();
        $this->trackingCompany = '';
        $this->trackingNumber = '';
        $this->trackingUrl = '';
        $this->dispatch('open-modal', name: 'create-fulfillment');
    }

    public function createFulfillment(): void
    {
        $this->authorize('create', Fulfillment::class);

        $lines = collect($this->fulfillmentLines)->filter(fn ($qty) => $qty > 0)->all();

        if (empty($lines)) {
            $this->dispatch('toast', type: 'error', message: 'Please select at least one line to fulfill.');

            return;
        }

        $tracking = null;
        if ($this->trackingCompany || $this->trackingNumber || $this->trackingUrl) {
            $tracking = [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber ?: null,
                'tracking_url' => $this->trackingUrl ?: null,
            ];
        }

        try {
            app(FulfillmentService::class)->create($this->order, $lines, $tracking);
            $this->order->refresh()->load([
                'lines.fulfillmentLines',
                'fulfillments.lines.orderLine',
            ]);
            $this->initFulfillmentLines();
            $this->dispatch('close-modal', name: 'create-fulfillment');
            $this->dispatch('toast', type: 'success', message: 'Fulfillment created successfully.');
        } catch (FulfillmentGuardException) {
            $this->dispatch('toast', type: 'error', message: 'Cannot create fulfillment. Payment must be confirmed first.');
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $this->authorize('update', $this->order);

        $fulfillment = Fulfillment::findOrFail($fulfillmentId);

        $tracking = null;
        if ($this->trackingCompany || $this->trackingNumber || $this->trackingUrl) {
            $tracking = [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber ?: null,
                'tracking_url' => $this->trackingUrl ?: null,
            ];
        }

        app(FulfillmentService::class)->markAsShipped($fulfillment, $tracking);
        $this->order->refresh()->load('fulfillments.lines.orderLine');
        $this->dispatch('toast', type: 'success', message: 'Fulfillment marked as shipped.');
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $this->authorize('update', $this->order);

        $fulfillment = Fulfillment::findOrFail($fulfillmentId);
        app(FulfillmentService::class)->markAsDelivered($fulfillment);
        $this->order->refresh()->load('fulfillments.lines.orderLine');
        $this->dispatch('toast', type: 'success', message: 'Fulfillment marked as delivered.');
    }

    public function openRefundModal(): void
    {
        $this->refundAmount = null;
        $this->refundReason = '';
        $this->refundRestock = false;
        $this->dispatch('open-modal', name: 'create-refund');
    }

    public function createRefund(): void
    {
        $this->authorize('create', \App\Models\Refund::class);

        $payment = $this->order->payments->first();
        if (! $payment) {
            $this->dispatch('toast', type: 'error', message: 'No payment found for this order.');

            return;
        }

        $amount = $this->refundAmount ? (int) ($this->refundAmount * 100) : $this->order->total_amount;

        try {
            app(RefundService::class)->create(
                $this->order,
                $payment,
                $amount,
                $this->refundReason ?: null,
                $this->refundRestock,
            );
            $this->order->refresh()->load('refunds');
            $this->dispatch('close-modal', name: 'create-refund');
            $this->dispatch('toast', type: 'success', message: 'Refund processed successfully.');
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * @return array<array{title: string, time: string}>
     */
    public function getTimelineProperty(): array
    {
        $events = [];

        if ($this->order->placed_at) {
            $events[] = [
                'title' => 'Order placed',
                'time' => $this->order->placed_at->format('M j, Y g:i A'),
            ];
        }

        foreach ($this->order->payments as $payment) {
            if ($payment->status === \App\Enums\PaymentStatus::Captured) {
                $events[] = [
                    'title' => 'Payment received',
                    'time' => $payment->captured_at?->format('M j, Y g:i A') ?? $payment->created_at->format('M j, Y g:i A'),
                ];
            }
        }

        foreach ($this->order->fulfillments as $fulfillment) {
            $events[] = [
                'title' => 'Fulfillment created',
                'time' => $fulfillment->created_at->format('M j, Y g:i A'),
            ];

            if ($fulfillment->shipped_at) {
                $events[] = [
                    'title' => 'Shipped',
                    'time' => $fulfillment->shipped_at->format('M j, Y g:i A'),
                ];
            }

            if ($fulfillment->delivered_at) {
                $events[] = [
                    'title' => 'Delivered',
                    'time' => $fulfillment->delivered_at->format('M j, Y g:i A'),
                ];
            }
        }

        foreach ($this->order->refunds as $refund) {
            $events[] = [
                'title' => 'Refund processed - '.number_format($refund->amount / 100, 2).' '.($this->order->currency ?? 'EUR'),
                'time' => $refund->created_at->format('M j, Y g:i A'),
            ];
        }

        if ($this->order->cancelled_at) {
            $events[] = [
                'title' => 'Order cancelled'.($this->order->cancel_reason ? ': '.$this->order->cancel_reason : ''),
                'time' => $this->order->cancelled_at->format('M j, Y g:i A'),
            ];
        }

        return $events;
    }

    public function canCreateFulfillment(): bool
    {
        return in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded])
            && $this->order->fulfillment_status !== FulfillmentStatus::Fulfilled;
    }

    public function canConfirmPayment(): bool
    {
        return $this->order->payment_method === PaymentMethod::BankTransfer
            && $this->order->financial_status === FinancialStatus::Pending;
    }

    public function canRefund(): bool
    {
        return in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded]);
    }

    private function initFulfillmentLines(): void
    {
        $this->fulfillmentLines = [];
        foreach ($this->order->lines as $line) {
            $fulfilledQty = $line->fulfillmentLines->sum('quantity');
            $unfulfilled = $line->quantity - $fulfilledQty;
            if ($unfulfilled > 0) {
                $this->fulfillmentLines[$line->id] = $unfulfilled;
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.orders.show')
            ->layout('layouts.admin', ['title' => 'Order #'.$this->order->order_number]);
    }
}
