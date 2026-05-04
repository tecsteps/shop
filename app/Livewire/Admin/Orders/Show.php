<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\RefundStatus;
use App\Exceptions\InvalidFulfillmentOperationException;
use App\Exceptions\InvalidOrderOperationException;
use App\Exceptions\InvalidRefundOperationException;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Refund;
use App\Models\Store;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Support\Money;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    #[Locked]
    public int $orderId;

    public string $refundAmount = '';

    public string $refundReason = '';

    /**
     * @var array<int, int>
     */
    public array $fulfillmentLineQuantities = [];

    public string $trackingCompany = '';

    public string $trackingNumber = '';

    public string $trackingUrl = '';

    public string $actionMessage = '';

    public function mount(Order $order): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $order = Order::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereKey($order->getKey())
            ->first();

        abort_unless($order instanceof Order, 404);

        $this->authorize('view', $order);

        $this->storeId = $store->getKey();
        $this->orderId = $order->getKey();
        $this->resetFulfillmentLineQuantities($this->order());
    }

    public function confirmBankTransferPayment(OrderService $orders): void
    {
        $this->authorize('update', $this->order());

        try {
            $orders->confirmBankTransferPayment($this->order());
            $this->resetFulfillmentLineQuantities($this->order());
            $this->actionMessage = __('Payment confirmed');
            $this->dispatch('toast', type: 'success', message: __('Payment confirmed'));
        } catch (InvalidOrderOperationException $exception) {
            throw ValidationException::withMessages([
                'orderAction' => $exception->getMessage(),
            ]);
        }
    }

    public function processRefund(RefundService $refunds): void
    {
        $this->authorize('createRefund', $this->order());

        $this->validate([
            'refundAmount' => ['nullable', 'numeric', 'min:0.01'],
            'refundReason' => ['nullable', 'string', 'max:500'],
        ]);

        $request = [
            'reason' => $this->refundReason !== '' ? $this->refundReason : null,
        ];

        if ($this->refundAmount !== '') {
            $request['amount'] = Money::fromDecimalString($this->refundAmount);
        }

        try {
            $refunds->process($this->order(), $request);
            $this->refundAmount = '';
            $this->refundReason = '';
            $this->actionMessage = __('Refund processed');
            $this->modal('refund-order')->close();
            $this->dispatch('toast', type: 'success', message: __('Refund processed'));
        } catch (InvalidRefundOperationException $exception) {
            throw ValidationException::withMessages([
                'refundAmount' => $exception->getMessage(),
            ]);
        }
    }

    public function createFulfillment(FulfillmentService $fulfillments): void
    {
        $this->authorize('createFulfillment', $this->order());

        $this->validate([
            'fulfillmentLineQuantities' => ['array'],
            'trackingCompany' => ['nullable', 'string', 'max:255'],
            'trackingNumber' => ['nullable', 'string', 'max:255'],
            'trackingUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        $lines = collect($this->fulfillmentLineQuantities)
            ->mapWithKeys(fn (mixed $quantity, int|string $lineId): array => [(int) $lineId => (int) $quantity])
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();

        if ($lines === []) {
            throw ValidationException::withMessages([
                'fulfillment' => __('At least one fulfillment line is required.'),
            ]);
        }

        try {
            $fulfillments->create($this->order(), $lines, [
                'tracking_company' => $this->trackingCompany !== '' ? $this->trackingCompany : null,
                'tracking_number' => $this->trackingNumber !== '' ? $this->trackingNumber : null,
                'tracking_url' => $this->trackingUrl !== '' ? $this->trackingUrl : null,
            ]);

            $this->trackingCompany = '';
            $this->trackingNumber = '';
            $this->trackingUrl = '';
            $this->resetFulfillmentLineQuantities($this->order());
            $this->actionMessage = __('Fulfillment created');
            $this->modal('fulfillment-order')->close();
            $this->dispatch('toast', type: 'success', message: __('Fulfillment created'));
        } catch (InvalidFulfillmentOperationException $exception) {
            throw ValidationException::withMessages([
                'fulfillment' => $exception->getMessage(),
            ]);
        }
    }

    public function markFulfillmentShipped(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $this->authorize('update', $this->fulfillment($fulfillmentId));

        try {
            $fulfillments->markShipped($this->fulfillment($fulfillmentId));
            $this->actionMessage = __('Fulfillment marked as shipped');
            $this->dispatch('toast', type: 'success', message: __('Fulfillment marked as shipped'));
        } catch (InvalidFulfillmentOperationException $exception) {
            throw ValidationException::withMessages([
                'fulfillment' => $exception->getMessage(),
            ]);
        }
    }

    public function markFulfillmentDelivered(int $fulfillmentId, FulfillmentService $fulfillments): void
    {
        $this->authorize('update', $this->fulfillment($fulfillmentId));

        try {
            $fulfillments->markDelivered($this->fulfillment($fulfillmentId));
            $this->actionMessage = __('Fulfillment marked as delivered');
            $this->dispatch('toast', type: 'success', message: __('Fulfillment marked as delivered'));
        } catch (InvalidFulfillmentOperationException $exception) {
            throw ValidationException::withMessages([
                'fulfillment' => $exception->getMessage(),
            ]);
        }
    }

    public function render(): mixed
    {
        $order = $this->order();

        return view('livewire.admin.orders.show', [
            'order' => $order,
            'remainingFulfillmentQuantities' => $this->remainingFulfillmentQuantities($order),
            'refundableAmount' => $this->refundableAmount($order),
        ])->layout('layouts.app', [
            'title' => $order->order_number,
        ]);
    }

    private function order(): Order
    {
        return Order::withoutGlobalScopes()
            ->with([
                'customer',
                'lines.fulfillmentLines',
                'payments',
                'refunds',
                'fulfillments.lines.orderLine',
            ])
            ->where('store_id', $this->storeId)
            ->whereKey($this->orderId)
            ->firstOrFail();
    }

    private function fulfillment(int $fulfillmentId): Fulfillment
    {
        $fulfillment = $this->order()
            ->fulfillments
            ->firstWhere('id', $fulfillmentId);

        abort_unless($fulfillment instanceof Fulfillment, 404);

        return $fulfillment;
    }

    /**
     * @return array<int, int>
     */
    private function remainingFulfillmentQuantities(Order $order): array
    {
        return $order->lines
            ->mapWithKeys(function (OrderLine $line): array {
                $fulfilled = $line->fulfillmentLines->sum('quantity');

                return [$line->getKey() => max(0, $line->quantity - $fulfilled)];
            })
            ->all();
    }

    private function resetFulfillmentLineQuantities(Order $order): void
    {
        $this->fulfillmentLineQuantities = $this->remainingFulfillmentQuantities($order);
    }

    private function refundableAmount(Order $order): int
    {
        $refunded = $order->refunds
            ->reject(fn (Refund $refund): bool => $refund->status === RefundStatus::Failed)
            ->sum('amount');

        return max(0, $order->total_amount - $refunded);
    }
}
