<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    use AuthorizesRequests;

    public Order $order;

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    public bool $showCancelModal = false;

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public int $refundAmount = 0;

    public string $refundReason = '';

    public bool $refundRestock = false;

    public function mount(Order $order): void
    {
        $this->authorize('view', Order::class);
        $this->order = $order->load(['lines', 'payments', 'fulfillments.lines', 'refunds', 'customer']);
        $this->refundAmount = $this->order->total_amount;
    }

    public function createFulfillment(): void
    {
        $this->authorize('manage', Order::class);

        $fulfillmentService = app(FulfillmentService::class);

        $lines = [];
        foreach ($this->order->lines as $line) {
            $lines[$line->id] = $line->quantity;
        }

        try {
            $fulfillmentService->create($this->order, $lines, [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber ?: null,
            ]);

            $this->order->refresh();
            $this->showFulfillmentModal = false;
            $this->trackingCompany = '';
            $this->trackingNumber = '';
            session()->flash('toast', ['type' => 'success', 'message' => __('Fulfillment created.')]);
        } catch (\Exception $e) {
            $this->addError('fulfillment', $e->getMessage());
        }
    }

    public function createRefund(): void
    {
        $this->authorize('manage', Order::class);

        $refundService = app(RefundService::class);
        $payment = $this->order->payments()->first();

        if (! $payment) {
            $this->addError('refund', __('No payment found for this order.'));

            return;
        }

        try {
            $refundService->create(
                $this->order,
                $payment,
                $this->refundAmount,
                $this->refundReason ?: null,
                $this->refundRestock,
            );

            $this->order->refresh();
            $this->showRefundModal = false;
            session()->flash('toast', ['type' => 'success', 'message' => __('Refund processed.')]);
        } catch (\Exception $e) {
            $this->addError('refund', $e->getMessage());
        }
    }

    public function cancelOrder(): void
    {
        $this->authorize('manage', Order::class);

        $orderService = app(OrderService::class);

        try {
            $orderService->cancel($this->order);
            $this->order->refresh();
            $this->showCancelModal = false;
            session()->flash('toast', ['type' => 'success', 'message' => __('Order cancelled.')]);
        } catch (\Exception $e) {
            $this->addError('cancel', $e->getMessage());
        }
    }

    public function confirmPayment(): void
    {
        $this->authorize('manage', Order::class);

        $orderService = app(OrderService::class);

        try {
            $orderService->confirmPayment($this->order);
            $this->order->refresh();
            session()->flash('toast', ['type' => 'success', 'message' => __('Payment confirmed.')]);
        } catch (\Exception $e) {
            $this->addError('payment', $e->getMessage());
        }
    }

    /**
     * Determine if the order can be fulfilled.
     */
    public function getCanFulfillProperty(): bool
    {
        return $this->order->fulfillment_status !== FulfillmentStatus::Fulfilled
            && in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded])
            && $this->order->status !== OrderStatus::Cancelled;
    }

    /**
     * Determine if the order can be refunded.
     */
    public function getCanRefundProperty(): bool
    {
        return in_array($this->order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded])
            && $this->order->status !== OrderStatus::Cancelled;
    }

    /**
     * Determine if the order can be cancelled.
     */
    public function getCanCancelProperty(): bool
    {
        return $this->order->fulfillment_status === FulfillmentStatus::Unfulfilled
            && $this->order->status !== OrderStatus::Cancelled
            && $this->order->status !== OrderStatus::Refunded;
    }

    /**
     * Determine if bank transfer payment can be confirmed.
     */
    public function getCanConfirmPaymentProperty(): bool
    {
        return $this->order->payment_method === PaymentMethod::BankTransfer
            && $this->order->financial_status === FinancialStatus::Pending;
    }

    public function render(): View
    {
        return view('livewire.admin.orders.show');
    }
}
