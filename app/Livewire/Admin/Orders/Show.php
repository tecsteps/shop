<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    use ResolvesAdminStore;

    public Order $order;

    /** @var array<int, int> order_line_id => quantity */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    /** @var array<int, bool> */
    public array $refundSelected = [];

    /** @var array<int, int> */
    public array $refundLines = [];

    public string $refundAmount = '';

    public string $refundReason = '';

    public bool $restock = false;

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    public function mount(Order $order): void
    {
        abort_if($order->store_id !== app(\App\Tenancy\CurrentStore::class)->id(), 404);

        Gate::authorize('view', $order);

        $this->order = $order;
    }

    public function confirmPayment(): void
    {
        Gate::authorize('confirmPayment', $this->order);

        try {
            $this->order = app(OrderService::class)->confirmBankTransferPayment($this->order);
            $this->toast('success', 'Payment confirmed.');
        } catch (\Throwable $exception) {
            $this->toast('error', $exception->getMessage());
        }
    }

    public function openFulfillmentModal(): void
    {
        Gate::authorize('createFulfillment', $this->order);

        $this->fulfillmentLines = [];

        foreach ($this->order->orderLines as $line) {
            $unfulfilled = $line->quantity - $line->fulfillmentLines->sum('quantity');

            if ($unfulfilled > 0) {
                $this->fulfillmentLines[$line->getKey()] = $unfulfilled;
            }
        }

        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(): void
    {
        Gate::authorize('createFulfillment', $this->order);

        $lines = array_filter(
            array_map('intval', $this->fulfillmentLines),
            fn (int $quantity): bool => $quantity > 0
        );

        try {
            app(FulfillmentService::class)->create($this->order, $lines, [
                'tracking_company' => $this->trackingCompany !== '' ? $this->trackingCompany : null,
                'tracking_number' => $this->trackingNumber !== '' ? $this->trackingNumber : null,
                'tracking_url' => $this->trackingUrl !== '' ? $this->trackingUrl : null,
            ]);

            $this->showFulfillmentModal = false;
            $this->fulfillmentLines = [];
            $this->trackingCompany = '';
            $this->trackingNumber = '';
            $this->trackingUrl = '';
            $this->order = $this->order->refresh();
            $this->toast('success', 'Fulfillment created.');
        } catch (\Throwable $exception) {
            $this->toast('error', $exception->getMessage());
        }
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->whereKey($fulfillmentId)->firstOrFail();

        Gate::authorize('update', $this->order);

        try {
            app(FulfillmentService::class)->markAsShipped($fulfillment);
            $this->order = $this->order->refresh();
            $this->toast('success', 'Fulfillment marked as shipped.');
        } catch (\Throwable $exception) {
            $this->toast('error', $exception->getMessage());
        }
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->whereKey($fulfillmentId)->firstOrFail();

        Gate::authorize('update', $this->order);

        try {
            app(FulfillmentService::class)->markAsDelivered($fulfillment);
            $this->order = $this->order->refresh();
            $this->toast('success', 'Fulfillment marked as delivered.');
        } catch (\Throwable $exception) {
            $this->toast('error', $exception->getMessage());
        }
    }

    public function openRefundModal(): void
    {
        Gate::authorize('createRefund', $this->order);

        $this->refundSelected = [];
        $this->refundLines = [];
        $this->refundAmount = '';
        $this->refundReason = '';
        $this->restock = false;
        $this->showRefundModal = true;
    }

    public function createRefund(): void
    {
        Gate::authorize('createRefund', $this->order);

        $lineItems = [];

        foreach ($this->refundSelected as $lineId => $selected) {
            if ($selected) {
                $line = $this->order->orderLines->firstWhere('id', (int) $lineId);

                if ($line !== null) {
                    $quantity = (int) ($this->refundLines[$lineId] ?? $line->quantity);
                    $lineItems[(int) $lineId] = max(1, min($quantity, $line->quantity));
                }
            }
        }

        $amount = 0;

        if ($this->refundAmount !== '') {
            $amount = (int) round(((float) $this->refundAmount) * 100);
        } else {
            foreach ($lineItems as $lineId => $quantity) {
                $line = $this->order->orderLines->firstWhere('id', $lineId);

                if ($line !== null) {
                    $amount += $line->unit_price_amount * $quantity;
                }
            }
        }

        try {
            app(RefundService::class)->create(
                $this->order,
                max(1, $amount),
                $lineItems === [] ? null : $lineItems,
                $this->refundReason !== '' ? $this->refundReason : null,
                $this->restock,
            );

            $this->showRefundModal = false;
            $this->order = $this->order->refresh();
            $this->toast('success', 'Refund issued.');
        } catch (\Throwable $exception) {
            $this->toast('error', $exception->getMessage());
        }
    }

    public function render()
    {
        $order = Order::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->order->getKey())
            ->with([
                'orderLines.variant.product.productMedia',
                'orderLines.fulfillmentLines',
                'payments',
                'refunds',
                'fulfillments.fulfillmentLines.orderLine',
                'customer',
            ])
            ->firstOrFail();

        $this->order = $order;

        $canFulfill = in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)
            && $order->fulfillment_status !== FulfillmentStatus::Fulfilled;

        $showConfirmPayment = $order->payment_method === PaymentMethod::BankTransfer
            && $order->financial_status === FinancialStatus::Pending;

        $showRefund = in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true);

        return view('livewire.admin.orders.show', [
            'order' => $order,
            'canFulfill' => $canFulfill,
            'showConfirmPayment' => $showConfirmPayment,
            'showRefund' => $showRefund,
            'payment' => $order->payments->sortByDesc('id')->first(),
        ])->layout('livewire.admin.layout.app', ['title' => 'Order '.$order->order_number]);
    }
}
