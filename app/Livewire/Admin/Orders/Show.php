<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    // Fulfillment modal
    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    /** @var array<int, int> */
    public array $fulfillLines = [];

    // Refund modal
    public string $refundAmount = '';

    public string $refundReason = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'lines',
            'customer',
            'payments',
            'refunds',
            'fulfillments.fulfillmentLines',
        ]);

        // Pre-populate fulfillment lines with unfulfilled quantities
        foreach ($this->order->lines as $line) {
            $fulfilled = $line->fulfillmentLines()->sum('quantity');
            $remaining = $line->quantity - $fulfilled;
            if ($remaining > 0) {
                $this->fulfillLines[$line->id] = $remaining;
            }
        }
    }

    public function confirmPayment(): void
    {
        if ($this->order->payment_method !== PaymentMethod::BankTransfer) {
            return;
        }

        $payment = $this->order->payments()->first();

        if ($payment) {
            $payment->update(['status' => PaymentStatus::Captured]);
        }

        $this->order->update([
            'financial_status' => FinancialStatus::Paid,
        ]);

        $this->order->refresh();
        $this->dispatch('toast', type: 'success', message: 'Payment confirmed.');
    }

    public function createFulfillment(): void
    {
        $lines = array_filter($this->fulfillLines, fn ($qty) => $qty > 0);

        if (empty($lines)) {
            $this->dispatch('toast', type: 'error', message: 'Please select items to fulfill.');

            return;
        }

        try {
            $service = app(FulfillmentService::class);
            $service->create($this->order, $lines, [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber ?: null,
                'tracking_url' => $this->trackingUrl ?: null,
            ]);

            $this->order->refresh();
            $this->order->load('fulfillments.fulfillmentLines');

            // Reset form
            $this->trackingCompany = '';
            $this->trackingNumber = '';
            $this->trackingUrl = '';

            // Update unfulfilled lines
            $this->fulfillLines = [];
            foreach ($this->order->lines as $line) {
                $fulfilled = $line->fulfillmentLines()->sum('quantity');
                $remaining = $line->quantity - $fulfilled;
                if ($remaining > 0) {
                    $this->fulfillLines[$line->id] = $remaining;
                }
            }

            $this->dispatch('toast', type: 'success', message: 'Fulfillment created.');
            $this->modal('create-fulfillment')->close();
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments->firstWhere('id', $fulfillmentId);

        if (! $fulfillment) {
            return;
        }

        try {
            app(FulfillmentService::class)->markAsShipped($fulfillment);
            $this->order->refresh();
            $this->order->load('fulfillments.fulfillmentLines');
            $this->dispatch('toast', type: 'success', message: 'Marked as shipped.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments->firstWhere('id', $fulfillmentId);

        if (! $fulfillment) {
            return;
        }

        try {
            app(FulfillmentService::class)->markAsDelivered($fulfillment);
            $this->order->refresh();
            $this->order->load('fulfillments.fulfillmentLines');
            $this->dispatch('toast', type: 'success', message: 'Marked as delivered.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function createRefund(): void
    {
        $amount = (int) round((float) $this->refundAmount * 100);

        if ($amount <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Please enter a valid refund amount.');

            return;
        }

        $payment = $this->order->payments()->first();

        if (! $payment) {
            $this->dispatch('toast', type: 'error', message: 'No payment found for this order.');

            return;
        }

        try {
            app(RefundService::class)->create(
                $this->order,
                $payment,
                $amount,
                $this->refundReason ?: null,
            );

            $this->order->refresh();
            $this->order->load('refunds');
            $this->refundAmount = '';
            $this->refundReason = '';

            $this->dispatch('toast', type: 'success', message: 'Refund processed.');
            $this->modal('create-refund')->close();
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function formatCurrency(int $amountInCents): string
    {
        return '$'.number_format($amountInCents / 100, 2);
    }

    public function render(): mixed
    {
        return view('livewire.admin.orders.show')
            ->layout('layouts.admin', [
                'breadcrumbs' => [
                    ['label' => 'Orders', 'url' => route('admin.orders.index')],
                    ['label' => $this->order->order_number],
                ],
            ]);
    }
}
