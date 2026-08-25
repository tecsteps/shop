<?php

namespace App\Livewire\Admin\Orders;

use App\Events\OrderPaid;
use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Livewire\Admin\Concerns\FormatsMoney;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\InventoryService;
use App\Services\RefundService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component
{
    use DispatchesToasts, FormatsMoney;

    #[Layout('layouts.admin.app')]
    public Order $order;

    /**
     * @var list<array{line_id: int, quantity: int}>
     */
    public array $fulfillmentLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public bool $showFulfillmentModal = false;

    public bool $showRefundModal = false;

    public ?float $refundAmount = null;

    public string $refundReason = '';

    /**
     * @var list<array{line_id: int, quantity: int, selected: bool}>
     */
    public array $refundLines = [];

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly FulfillmentService $fulfillmentService,
        private readonly RefundService $refundService,
    ) {}

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);

        $this->order = $order->load([
            'lines.variant.product',
            'payments',
            'fulfillments.lines.orderLine',
            'customer',
            'refunds',
        ]);

        $this->initFulfillmentLines();
    }

    #[Computed]
    public function canFulfill(): bool
    {
        return in_array($this->order->financial_status, ['paid', 'partially_refunded'], true);
    }

    #[Computed]
    public function isFullyFulfilled(): bool
    {
        return $this->order->fulfillment_status === 'fulfilled';
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function unfulfilledQuantities(): array
    {
        $fulfilled = FulfillmentLine::whereIn('order_line_id', $this->order->lines->pluck('id'))
            ->whereHas('fulfillment', fn ($q) => $q->where('order_id', $this->order->id))
            ->selectRaw('order_line_id, SUM(quantity) as total')
            ->groupBy('order_line_id')
            ->pluck('total', 'order_line_id');

        return $this->order->lines->mapWithKeys(fn ($line) => [
            $line->id => max(0, $line->quantity - (int) ($fulfilled[$line->id] ?? 0)),
        ])->all();
    }

    /**
     * @return list<array{title: string, time: \Illuminate\Support\Carbon|null}>
     */
    #[Computed]
    public function timeline(): array
    {
        $events = [
            ['title' => 'Order placed', 'time' => $this->order->placed_at],
        ];

        if (in_array($this->order->financial_status, ['paid', 'partially_refunded', 'refunded'], true)) {
            $events[] = ['title' => 'Payment received', 'time' => $this->order->payments->first()?->created_at];
        }

        foreach ($this->order->fulfillments as $fulfillment) {
            $events[] = ['title' => 'Fulfillment created', 'time' => $fulfillment->created_at];

            if ($fulfillment->shipped_at) {
                $events[] = ['title' => 'Shipped', 'time' => $fulfillment->shipped_at];
            }

            if ($fulfillment->delivered_at) {
                $events[] = ['title' => 'Delivered', 'time' => $fulfillment->delivered_at];
            }
        }

        foreach ($this->order->refunds as $refund) {
            $events[] = ['title' => 'Refunded', 'time' => $refund->created_at];
        }

        return collect($events)->sortByDesc('time')->values()->all();
    }

    public function confirmPayment(): void
    {
        $this->authorize('update', $this->order);

        if ($this->order->payment_method !== 'bank_transfer' || $this->order->financial_status !== 'pending') {
            return;
        }

        try {
            DB::transaction(function () {
                $this->order->payments()->update(['status' => 'captured']);
                $this->order->update(['financial_status' => 'paid', 'status' => 'paid']);

                foreach ($this->order->lines()->with('variant.inventoryItem')->get() as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventoryService->commit($line->variant->inventoryItem, $line->quantity);
                    }
                }

                OrderPaid::dispatch($this->order);
                $this->fulfillmentService->autoFulfillDigital($this->order);
            });

            $this->reloadOrder();
            $this->toast('Payment confirmed');
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function openFulfillmentModal(): void
    {
        $this->initFulfillmentLines();
        $this->trackingCompany = '';
        $this->trackingNumber = '';
        $this->trackingUrl = '';
        $this->showFulfillmentModal = true;
    }

    public function createFulfillment(): void
    {
        $this->authorize('createFulfillment', $this->order);

        $lines = collect($this->fulfillmentLines)
            ->filter(fn ($row) => (int) ($row['quantity'] ?? 0) > 0)
            ->map(fn ($row) => [
                'order_line_id' => (int) $row['line_id'],
                'quantity' => (int) $row['quantity'],
            ])
            ->values()
            ->all();

        if ($lines === []) {
            $this->toast('Select at least one line to fulfill', 'error');

            return;
        }

        $tracking = array_filter([
            'tracking_company' => $this->trackingCompany,
            'tracking_number' => $this->trackingNumber,
            'tracking_url' => $this->trackingUrl,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $this->fulfillmentService->create($this->order, $lines, $tracking !== [] ? $tracking : null);

            $this->showFulfillmentModal = false;
            $this->reloadOrder();
            $this->toast('Fulfillment created');
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function markAsShipped(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->find($fulfillmentId);

        if (! $fulfillment) {
            return;
        }

        $this->authorize('update', $fulfillment);

        try {
            $this->fulfillmentService->markAsShipped($fulfillment);
            $this->reloadOrder();
            $this->toast('Fulfillment marked as shipped');
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function markAsDelivered(int $fulfillmentId): void
    {
        $fulfillment = $this->order->fulfillments()->find($fulfillmentId);

        if (! $fulfillment) {
            return;
        }

        $this->authorize('update', $fulfillment);

        try {
            $this->fulfillmentService->markAsDelivered($fulfillment);
            $this->markFulfilledWhenComplete();
            $this->reloadOrder();
            $this->toast('Fulfillment marked as delivered');
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function openRefundModal(): void
    {
        $this->refundAmount = null;
        $this->refundReason = '';
        $this->refundLines = $this->order->lines->map(fn ($line) => [
            'line_id' => $line->id,
            'quantity' => 0,
            'selected' => false,
        ])->all();
        $this->showRefundModal = true;
    }

    public function createRefund(): void
    {
        $this->authorize('createRefund', $this->order);

        $payment = $this->order->payments()->first();

        if (! $payment) {
            $this->toast('No payment available to refund', 'error');

            return;
        }

        $amount = null;

        if ($this->refundAmount !== null && (float) $this->refundAmount > 0) {
            $amount = (int) round((float) $this->refundAmount * 100);
        } else {
            $linesById = $this->order->lines->keyBy('id');

            $amount = collect($this->refundLines)
                ->filter(fn ($row) => (bool) ($row['selected'] ?? false))
                ->sum(function ($row) use ($linesById) {
                    $line = $linesById->get($row['line_id']);

                    if (! $line) {
                        return 0;
                    }

                    $quantity = min((int) ($row['quantity'] ?? 0), (int) $line->quantity);

                    return (int) $line->unit_price_amount * $quantity;
                });
        }

        if (! $amount || $amount <= 0) {
            $this->toast('Enter a refund amount', 'error');

            return;
        }

        try {
            $this->refundService->create(
                $this->order,
                $payment,
                $amount,
                $this->refundReason !== '' ? $this->refundReason : null,
                true,
            );

            $this->showRefundModal = false;
            $this->reloadOrder();
            $this->toast('Refund issued');
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    public function markFulfilledWhenComplete(): void
    {
        $order = $this->order->fresh('lines');

        if (! $order) {
            return;
        }

        $allDelivered = $this->order->fulfillments->every(fn ($f) => $f->status === 'delivered');
        $allLinesFulfilled = true;

        foreach ($order->lines as $line) {
            $fulfilled = FulfillmentLine::where('order_line_id', $line->id)
                ->whereHas('fulfillment', fn ($q) => $q->where('order_id', $order->id))
                ->sum('quantity');

            if ($fulfilled < $line->quantity) {
                $allLinesFulfilled = false;

                break;
            }
        }

        if ($allDelivered && $allLinesFulfilled) {
            $order->update(['fulfillment_status' => 'fulfilled', 'status' => 'fulfilled']);
        }
    }

    private function reloadOrder(): void
    {
        $this->order->refresh();
        $this->order->load([
            'lines.variant.product',
            'payments',
            'fulfillments.lines.orderLine',
            'customer',
            'refunds',
        ]);
    }

    private function initFulfillmentLines(): void
    {
        $this->fulfillmentLines = $this->order->lines->map(fn ($line) => [
            'line_id' => $line->id,
            'quantity' => 0,
        ])->all();
    }

    public function render()
    {
        return view('livewire.admin.orders.show');
    }
}
