<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class Show extends Component
{
    use UsesAdminStore;

    public Order $order;

    public int $refundAmount = 0;

    public string $refundReason = '';

    public bool $restockRefund = false;

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);

        $this->order = $order;
    }

    public function confirmBankTransfer(OrderService $orders): void
    {
        Gate::authorize('update', $this->order);

        try {
            $this->order = $orders->confirmBankTransfer($this->order);
            $this->notify('Payment confirmed.');
        } catch (Throwable $exception) {
            $this->addError('order', $exception->getMessage());
        }
    }

    public function fulfillAll(FulfillmentService $fulfillments): void
    {
        Gate::authorize('fulfill', $this->order);

        try {
            $this->order->load('lines.fulfillmentLines');
            $lines = [];

            foreach ($this->order->lines as $line) {
                $fulfilled = (int) $line->fulfillmentLines->sum('quantity');
                $remaining = $line->quantity - $fulfilled;

                if ($remaining > 0) {
                    $lines[$line->id] = $remaining;
                }
            }

            $fulfillments->create($this->order, $lines, [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber ?: null,
                'tracking_url' => $this->trackingUrl ?: null,
            ]);

            $this->order = $this->order->refresh();
            $this->reset('trackingCompany', 'trackingNumber', 'trackingUrl');
            $this->notify('Fulfillment created.');
        } catch (Throwable $exception) {
            $this->addError('order', $exception->getMessage());
        }
    }

    public function markFulfillmentShipped(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $fulfillment = $this->fulfillment($fulfillmentId);
        Gate::authorize('update', $fulfillment);

        try {
            $fulfillments->markAsShipped($fulfillment);
            $this->order = $this->order->refresh();
            $this->notify('Fulfillment marked as shipped.');
        } catch (Throwable $exception) {
            $this->addError('order', $exception->getMessage());
        }
    }

    public function markFulfillmentDelivered(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $fulfillment = $this->fulfillment($fulfillmentId);
        Gate::authorize('update', $fulfillment);

        try {
            $fulfillments->markAsDelivered($fulfillment);
            $this->order = $this->order->refresh();
            $this->notify('Fulfillment marked as delivered.');
        } catch (Throwable $exception) {
            $this->addError('order', $exception->getMessage());
        }
    }

    public function refund(RefundService $refunds): void
    {
        Gate::authorize('refund', $this->order);

        $this->validate([
            'refundAmount' => ['required', 'integer', 'min:1'],
            'refundReason' => ['nullable', 'string', 'max:500'],
            'restockRefund' => ['bool'],
        ]);

        try {
            $payment = $this->order->payments()
                ->where('status', PaymentStatus::Captured)
                ->latest('id')
                ->firstOrFail();

            $refunds->create($this->order, $payment, $this->refundAmount, $this->refundReason ?: null, $this->restockRefund);
            $this->reset('refundAmount', 'refundReason', 'restockRefund');
            $this->notify('Refund processed.');
        } catch (Throwable $exception) {
            $this->addError('order', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $this->order->load('customer', 'lines.fulfillmentLines', 'payments.refunds', 'refunds', 'fulfillments.lines.orderLine');
        $paymentAllowsFulfillment = in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true);
        $isFullyFulfilled = $this->order->fulfillment_status === FulfillmentStatus::Fulfilled;

        return view('livewire.admin.orders.show', [
            'canConfirmBankTransfer' => $this->order->payment_method->value === 'bank_transfer'
                && $this->order->financial_status === FinancialStatus::Pending,
            'canFulfill' => $paymentAllowsFulfillment && ! $isFullyFulfilled,
            'fulfillmentGuardMessage' => match (true) {
                ! $paymentAllowsFulfillment => 'Payment must be confirmed before items can be fulfilled.',
                $isFullyFulfilled => 'All line items have been fulfilled.',
                default => null,
            },
        ])->layout('livewire.admin.layout.app', [
            'title' => $this->order->order_number,
        ]);
    }

    private function fulfillment(int $fulfillmentId): Fulfillment
    {
        return $this->order
            ->fulfillments()
            ->whereKey($fulfillmentId)
            ->firstOrFail();
    }
}
