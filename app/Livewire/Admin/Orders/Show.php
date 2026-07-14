<?php

namespace App\Livewire\Admin\Orders;

use App\Livewire\Admin\AdminComponent;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Support\SafeUrl;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class Show extends AdminComponent
{
    public Order $order;

    /** @var array<int|string, int> */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public ?int $refundAmount = null;

    public string $refundReason = '';

    /** @var array<int|string, int> */
    public array $refundLines = [];

    public function mount(Order $order): void
    {
        abort_unless((int) $order->store_id === (int) $this->currentStore()->id, 404);
        $this->authorizeAction('view', $order);
        $this->order = $order;
        $this->reloadOrder();
        foreach ($this->order->lines as $line) {
            $fulfilled = (int) $line->fulfillmentLines->sum('quantity');
            $this->fulfillmentLines[$line->id] = max(0, (int) $line->quantity - $fulfilled);
            $this->refundLines[$line->id] = 0;
        }
    }

    public function openFulfillmentModal(): void
    {
        foreach ($this->order->lines as $line) {
            $this->fulfillmentLines[$line->id] = max(0, (int) $line->quantity - (int) $line->fulfillmentLines->sum('quantity'));
        }
    }

    public function openRefundModal(): void
    {
        $refunded = (int) $this->order->refunds
            ->filter(fn ($refund): bool => $this->enumValue($refund->status) === 'processed')
            ->sum('amount');
        $this->refundAmount = max(0, (int) $this->order->total_amount - $refunded);
    }

    public function confirmPayment(): void
    {
        $this->authorizeAction('update', $this->order);
        try {
            app(OrderService::class)->confirmBankTransfer($this->order);
            $this->reloadOrder();
            $this->toast('Bank transfer payment confirmed.');
        } catch (\Throwable $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    public function createFulfillment(): void
    {
        $this->authorizeAction('fulfill', $this->order);
        $this->validate([
            'fulfillmentLines' => ['required', 'array'], 'fulfillmentLines.*' => ['integer', 'min:0'],
            'trackingCompany' => ['nullable', 'string', 'max:255'], 'trackingNumber' => ['nullable', 'string', 'max:255'], 'trackingUrl' => [
                'nullable', 'string', 'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! SafeUrl::isAllowed($value)) {
                        $fail('The tracking link must be a relative, HTTP, or HTTPS URL.');
                    }
                },
            ],
        ]);
        $lines = collect($this->fulfillmentLines)->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])->filter(fn (int $quantity) => $quantity > 0)->all();
        if ($lines === []) {
            throw ValidationException::withMessages(['fulfillmentLines' => 'Select at least one quantity to fulfill.']);
        }
        try {
            app(FulfillmentService::class)->create($this->order, $lines, ['tracking_company' => $this->trackingCompany ?: null, 'tracking_number' => $this->trackingNumber ?: null, 'tracking_url' => $this->trackingUrl ?: null]);
            $this->reloadOrder();
            $this->toast('Fulfillment created.');
            $this->modal('create-fulfillment')->close();
        } catch (\Throwable $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $this->authorizeAction('fulfill', $this->order);
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        app(FulfillmentService::class)->markAsShipped($fulfillment);
        $this->reloadOrder();
        $this->toast('Fulfillment marked as shipped.');
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $this->authorizeAction('fulfill', $this->order);
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);
        app(FulfillmentService::class)->markAsDelivered($fulfillment);
        $this->reloadOrder();
        if ($this->order->fulfillments->isNotEmpty() && $this->order->fulfillments->every(fn (Fulfillment $item): bool => $this->enumValue($item->status) === 'delivered')) {
            $this->order->update(['fulfillment_status' => 'fulfilled', 'status' => 'fulfilled']);
            $this->reloadOrder();
        }
        $this->toast('Fulfillment marked as delivered.');
    }

    public function createRefund(): void
    {
        $this->authorizeAction('refund', $this->order);
        $this->validate(['refundAmount' => ['nullable', 'integer', 'min:1'], 'refundReason' => ['nullable', 'string', 'max:1000'], 'refundLines.*' => ['integer', 'min:0']]);
        $payment = $this->order->payments->first(fn ($payment) => in_array($this->enumValue($payment->status), ['captured', 'refunded'], true));
        if (! $payment) {
            throw ValidationException::withMessages(['refundAmount' => 'This order has no captured payment.']);
        }
        $lines = collect($this->refundLines)->mapWithKeys(fn ($quantity, $id) => [(int) $id => (int) $quantity])->filter()->all();
        $amount = $this->refundAmount ?: (int) $this->order->lines->sum(fn ($line) => (int) ($lines[$line->id] ?? 0) * (int) $line->unit_price_amount);
        try {
            app(RefundService::class)->create($this->order, $payment, $amount, $this->refundReason ?: null, $lines !== [], $lines ?: null);
            $this->reloadOrder();
            $this->toast('Refund processed.');
            $this->modal('create-refund')->close();
        } catch (\Throwable $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    private function reloadOrder(): void
    {
        $this->order = $this->order->refresh()->load(['customer', 'lines.product.media', 'lines.variant', 'lines.fulfillmentLines', 'payments', 'refunds', 'fulfillments.lines.orderLine']);
    }

    public function render(): View
    {
        return $this->admin(view('admin.orders.show'), $this->order->order_number, [['label' => 'Orders', 'url' => url('/admin/orders')], ['label' => $this->order->order_number]]);
    }
}
