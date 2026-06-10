<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Order;
use App\Models\OrderLine;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts::admin')]
class Show extends Component
{
    use AuthorizesRequests, SendsToasts;

    #[Locked]
    public int $orderId;

    /**
     * Fulfillment modal state keyed by order line id.
     *
     * @var array<int, array{selected: bool, quantity: int|string, max: int, title: string}>
     */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    /**
     * Refund modal state keyed by order line id.
     *
     * @var array<int, array{selected: bool, quantity: int|string, max: int, title: string, unit: int}>
     */
    public array $refundLines = [];

    public string $refundAmount = '';

    public string $refundReason = '';

    public bool $refundRestock = false;

    public string $cancelReason = '';

    public function mount(int $order): void
    {
        $this->orderId = $order;

        $this->authorize('view', $this->order);

        $this->prepareModalState();
    }

    #[Computed]
    public function order(): Order
    {
        return Order::query()
            ->with([
                'customer',
                'lines.variant.product.media',
                'lines.fulfillmentLines',
                'payments',
                'refunds',
                'fulfillments.lines.orderLine',
            ])
            ->findOrFail($this->orderId);
    }

    /**
     * Admin "Confirm Payment" for pending bank transfer orders.
     */
    public function confirmPayment(): void
    {
        $this->authorize('update', $this->order);

        try {
            app(OrderService::class)->confirmBankTransferPayment($this->order);
        } catch (ValidationException $exception) {
            $this->toast($this->firstError($exception), 'error');

            return;
        }

        $this->refreshOrder();
        $this->toast(__('Payment confirmed'));
    }

    public function createFulfillment(): void
    {
        $this->authorize('createFulfillment', $this->order);

        $lines = collect($this->fulfillmentLines)
            ->filter(fn (array $line): bool => $line['selected'] && (int) $line['quantity'] > 0)
            ->map(fn (array $line): int => (int) $line['quantity'])
            ->all();

        try {
            app(FulfillmentService::class)->create($this->order, $lines, [
                'tracking_company' => $this->trackingCompany !== '' ? $this->trackingCompany : null,
                'tracking_number' => $this->trackingNumber !== '' ? $this->trackingNumber : null,
                'tracking_url' => $this->trackingUrl !== '' ? $this->trackingUrl : null,
            ]);
        } catch (FulfillmentGuardException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return;
        } catch (ValidationException $exception) {
            $this->toast($this->firstError($exception), 'error');

            return;
        }

        Flux::modal('create-fulfillment')->close();

        $this->reset('trackingCompany', 'trackingNumber', 'trackingUrl');
        $this->refreshOrder();
        $this->toast(__('Fulfillment created'));
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);

        $this->authorize('update', $fulfillment);

        app(FulfillmentService::class)->markAsShipped($fulfillment);

        $this->refreshOrder();
        $this->toast(__('Fulfillment marked as shipped'));
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->findOrFail($fulfillmentId);

        $this->authorize('update', $fulfillment);

        app(FulfillmentService::class)->markAsDelivered($fulfillment);

        $this->refreshOrder();
        $this->toast(__('Fulfillment marked as delivered'));
    }

    /**
     * Refund a custom amount or the total of the selected lines.
     */
    public function createRefund(): void
    {
        $this->authorize('createRefund', $this->order);

        $amount = $this->resolveRefundAmount();

        if ($amount < 1) {
            $this->toast(__('Select lines or enter a refund amount.'), 'error');

            return;
        }

        $payment = $this->order->payments->firstWhere('status', PaymentStatus::Captured);

        if ($payment === null) {
            $this->toast(__('No captured payment is available to refund.'), 'error');

            return;
        }

        try {
            app(RefundService::class)->create(
                $this->order,
                $payment,
                $amount,
                $this->refundReason !== '' ? $this->refundReason : null,
                $this->refundRestock,
            );
        } catch (ValidationException $exception) {
            $this->toast($this->firstError($exception), 'error');

            return;
        }

        Flux::modal('create-refund')->close();

        $this->reset('refundAmount', 'refundReason', 'refundRestock');
        $this->refreshOrder();
        $this->toast(__('Refund issued'));
    }

    public function cancelOrder(): void
    {
        $this->authorize('cancel', $this->order);

        try {
            app(OrderService::class)->cancel($this->order, $this->cancelReason !== '' ? $this->cancelReason : null);
        } catch (ValidationException $exception) {
            $this->toast($this->firstError($exception), 'error');

            return;
        }

        Flux::modal('cancel-order')->close();

        $this->refreshOrder();
        $this->toast(__('Order cancelled'));
    }

    /**
     * Chronological order history: placed, payment, fulfillments, refunds,
     * cancellation (spec 03 section 8 timeline).
     *
     * @return list<array{label: string, description: string|null, timestamp: Carbon}>
     */
    #[Computed]
    public function timeline(): array
    {
        $order = $this->order;
        $events = [];

        if ($order->placed_at !== null) {
            $events[] = ['label' => __('Order placed'), 'description' => null, 'timestamp' => $order->placed_at];
        }

        $capturedPayment = $order->payments->firstWhere('status', PaymentStatus::Captured)
            ?? $order->payments->firstWhere('status', PaymentStatus::Refunded);

        if ($capturedPayment !== null) {
            $events[] = [
                'label' => __('Payment received'),
                'description' => __('Paid via :method', ['method' => str_replace('_', ' ', $capturedPayment->method->value)]),
                'timestamp' => $capturedPayment->updated_at ?? $capturedPayment->created_at,
            ];
        }

        foreach ($order->fulfillments as $fulfillment) {
            $events[] = [
                'label' => __('Fulfillment created'),
                'description' => filled($fulfillment->tracking_number)
                    ? __('Tracking: :tracking', ['tracking' => trim(($fulfillment->tracking_company ?? '').' '.$fulfillment->tracking_number)])
                    : null,
                'timestamp' => $fulfillment->created_at,
            ];

            if ($fulfillment->shipped_at !== null) {
                $events[] = ['label' => __('Shipped'), 'description' => null, 'timestamp' => $fulfillment->shipped_at];
            }

            if ($fulfillment->delivered_at !== null) {
                $events[] = ['label' => __('Delivered'), 'description' => null, 'timestamp' => $fulfillment->delivered_at];
            }
        }

        foreach ($order->refunds as $refund) {
            if ($refund->status !== RefundStatus::Processed) {
                continue;
            }

            $events[] = [
                'label' => __('Refunded'),
                'description' => \App\Support\Storefront\PriceFormatter::format($refund->amount, $order->currency)
                    .(filled($refund->reason) ? ' - '.$refund->reason : ''),
                'timestamp' => $refund->created_at,
            ];
        }

        if ($order->status === OrderStatus::Cancelled) {
            $events[] = ['label' => __('Order cancelled'), 'description' => null, 'timestamp' => $order->updated_at];
        }

        usort($events, fn (array $a, array $b): int => $a['timestamp'] <=> $b['timestamp']);

        return $events;
    }

    #[Computed]
    public function canCreateFulfillment(): bool
    {
        return $this->order->financial_status->allowsFulfillment()
            && $this->order->status !== OrderStatus::Cancelled
            && $this->order->lines->contains(fn (OrderLine $line): bool => $line->unfulfilledQuantity() > 0);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.show')
            ->title(__('Order :number', ['number' => $this->order->order_number]));
    }

    protected function refreshOrder(): void
    {
        unset($this->order, $this->timeline, $this->canCreateFulfillment);

        $this->prepareModalState();
    }

    /**
     * Seed the fulfillment and refund modal line selections from the order.
     */
    protected function prepareModalState(): void
    {
        $this->fulfillmentLines = [];
        $this->refundLines = [];

        foreach ($this->order->lines as $line) {
            $unfulfilled = $line->unfulfilledQuantity();

            if ($unfulfilled > 0) {
                $this->fulfillmentLines[$line->getKey()] = [
                    'selected' => false,
                    'quantity' => $unfulfilled,
                    'max' => $unfulfilled,
                    'title' => $line->title_snapshot,
                ];
            }

            $this->refundLines[$line->getKey()] = [
                'selected' => false,
                'quantity' => $line->quantity,
                'max' => $line->quantity,
                'title' => $line->title_snapshot,
                'unit' => $line->unit_price_amount,
            ];
        }
    }

    /**
     * The custom refund amount in minor units, or the sum of the selected
     * refund lines when no custom amount was entered.
     */
    protected function resolveRefundAmount(): int
    {
        if (trim($this->refundAmount) !== '') {
            return (int) round((float) str_replace(',', '.', $this->refundAmount) * 100);
        }

        return (int) collect($this->refundLines)
            ->filter(fn (array $line): bool => $line['selected'] && (int) $line['quantity'] > 0)
            ->sum(fn (array $line): int => min((int) $line['quantity'], $line['max']) * $line['unit']);
    }

    protected function firstError(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? __('Something went wrong. Please try again.');
    }
}
