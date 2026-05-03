<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
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

    public bool $refundUseCustomAmount = false;

    /**
     * @var array<int, int>
     */
    public array $fulfillmentLines = [];

    /**
     * @var array<int, int>
     */
    public array $refundLines = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);

        $this->order = $order;
        $this->resetLineInputs();
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
        $this->order->load('lines.fulfillmentLines');
        $this->fulfillmentLines = $this->remainingFulfillmentQuantities();

        $this->createFulfillment($fulfillments);
    }

    public function createFulfillment(FulfillmentService $fulfillments): void
    {
        Gate::authorize('fulfill', $this->order);

        $this->validate([
            'fulfillmentLines' => ['array'],
            'fulfillmentLines.*' => ['integer', 'min:0'],
            'trackingCompany' => ['nullable', 'string', 'max:255'],
            'trackingNumber' => ['nullable', 'string', 'max:255'],
            'trackingUrl' => ['nullable', 'url', 'max:255'],
        ]);

        try {
            $fulfillments->create($this->order, $this->positiveQuantities($this->fulfillmentLines), [
                'tracking_company' => $this->trackingCompany ?: null,
                'tracking_number' => $this->trackingNumber ?: null,
                'tracking_url' => $this->trackingUrl ?: null,
            ]);

            $this->order = $this->order->refresh();
            $this->reset('trackingCompany', 'trackingNumber', 'trackingUrl');
            $this->resetLineInputs();
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

        $lineQuantities = $this->positiveQuantities($this->refundLines);

        if ($lineQuantities === [] && ! $this->refundUseCustomAmount) {
            $this->addError('order', 'Select at least one line quantity to refund or enable a custom amount.');

            return;
        }

        $this->validate([
            'refundAmount' => $this->refundUseCustomAmount || $lineQuantities === []
                ? ['required', 'integer', 'min:1']
                : ['integer', 'min:0'],
            'refundReason' => ['nullable', 'string', 'max:500'],
            'restockRefund' => ['bool'],
            'refundUseCustomAmount' => ['bool'],
            'refundLines' => ['array'],
            'refundLines.*' => ['integer', 'min:0'],
        ]);

        try {
            $payment = $this->order->payments()
                ->where('status', PaymentStatus::Captured)
                ->latest('id')
                ->firstOrFail();

            if ($lineQuantities === []) {
                $refunds->create($this->order, $payment, $this->refundAmount, $this->refundReason ?: null, $this->restockRefund);
            } else {
                $refunds->createForLines(
                    $this->order,
                    $payment,
                    $lineQuantities,
                    $this->refundReason ?: null,
                    $this->restockRefund,
                    $this->refundUseCustomAmount ? $this->refundAmount : null,
                );
            }

            $this->order = $this->order->refresh();
            $this->reset('refundAmount', 'refundReason', 'restockRefund', 'refundUseCustomAmount');
            $this->resetLineInputs();
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
            'lineStates' => $this->lineStates(),
            'computedRefundAmount' => $this->computedRefundAmount(),
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

    /**
     * @return array<int, int>
     */
    private function remainingFulfillmentQuantities(): array
    {
        return $this->order->lines
            ->mapWithKeys(function (OrderLine $line): array {
                $fulfilled = (int) $line->fulfillmentLines->sum('quantity');

                return [$line->id => max(0, $line->quantity - $fulfilled)];
            })
            ->all();
    }

    private function resetLineInputs(): void
    {
        $this->order->load('lines.fulfillmentLines');
        $this->fulfillmentLines = $this->remainingFulfillmentQuantities();
        $this->refundLines = $this->order->lines
            ->mapWithKeys(fn (OrderLine $line): array => [$line->id => 0])
            ->all();
    }

    /**
     * @param  array<int, int>  $quantities
     * @return array<int, int>
     */
    private function positiveQuantities(array $quantities): array
    {
        return collect($quantities)
            ->map(fn (mixed $quantity): int => (int) $quantity)
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();
    }

    /**
     * @return array<int, array{fulfilled: int, unfulfilled: int, refund_amount: int}>
     */
    private function lineStates(): array
    {
        return $this->order->lines
            ->mapWithKeys(function (OrderLine $line): array {
                $fulfilled = (int) $line->fulfillmentLines->sum('quantity');

                return [
                    $line->id => [
                        'fulfilled' => $fulfilled,
                        'unfulfilled' => max(0, $line->quantity - $fulfilled),
                        'refund_amount' => $this->lineRefundAmount($line, (int) ($this->refundLines[$line->id] ?? 0)),
                    ],
                ];
            })
            ->all();
    }

    private function computedRefundAmount(): int
    {
        return $this->order->lines->sum(
            fn (OrderLine $line): int => $this->lineRefundAmount($line, (int) ($this->refundLines[$line->id] ?? 0)),
        );
    }

    private function lineRefundAmount(OrderLine $line, int $quantity): int
    {
        if ($quantity < 1) {
            return 0;
        }

        return $quantity >= $line->quantity
            ? $line->total_amount
            : intdiv($line->total_amount * $quantity, $line->quantity);
    }
}
