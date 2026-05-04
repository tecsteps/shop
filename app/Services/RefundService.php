<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\InvalidRefundOperationException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory,
    ) {}

    /**
     * @param  array{amount?: int, lines?: array<int|string, int>, reason?: string|null, restock?: bool}  $request
     */
    public function process(Order $order, array $request = []): Refund
    {
        return DB::transaction(function () use ($order, $request): Refund {
            $order = $this->freshOrder($order);
            $payment = $this->refundablePayment($order);
            $refundable = $this->refundableAmount($order);
            $lineQuantities = $this->lineQuantities($order, $request['lines'] ?? []);
            $amount = $this->refundAmount($order, $request, $lineQuantities, $refundable);

            $result = $this->payments->refund($payment, $amount);

            if (! $result->success) {
                throw InvalidRefundOperationException::because($result->errorMessage ?? 'Refund could not be processed.');
            }

            $refund = $order->refunds()->create([
                'payment_id' => $payment->getKey(),
                'amount' => $amount,
                'reason' => data_get($request, 'reason'),
                'status' => $result->status,
                'provider_refund_id' => $result->referenceId,
            ]);

            if ((bool) data_get($request, 'restock', false)) {
                $this->restock($order, $lineQuantities);
            }

            $this->updateOrderFinancialStatus($order, $payment);

            $order = $order->refresh();
            $refund = $refund->refresh();

            event(new OrderRefunded($order, $refund));

            return $refund;
        });
    }

    private function freshOrder(Order $order): Order
    {
        return Order::withoutGlobalScopes()
            ->with(['lines', 'payments', 'refunds'])
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function refundablePayment(Order $order): Payment
    {
        $payment = $order->payments
            ->where('status', PaymentStatus::Captured)
            ->sortByDesc('id')
            ->first();

        if (! $payment instanceof Payment) {
            throw InvalidRefundOperationException::because('Order does not have a captured payment to refund.');
        }

        return $payment;
    }

    private function refundableAmount(Order $order): int
    {
        $refunded = $order->refunds
            ->reject(fn (Refund $refund): bool => $refund->status === RefundStatus::Failed)
            ->sum('amount');

        return $order->total_amount - $refunded;
    }

    /**
     * @param  array<string, mixed>  $request
     * @param  Collection<int, int>  $lineQuantities
     */
    private function refundAmount(Order $order, array $request, Collection $lineQuantities, int $refundable): int
    {
        $amount = array_key_exists('amount', $request)
            ? (int) $request['amount']
            : ($lineQuantities->isNotEmpty() ? $this->lineRefundAmount($order, $lineQuantities) : $refundable);

        if ($amount <= 0) {
            throw InvalidRefundOperationException::because('Refund amount must be greater than zero.');
        }

        if ($amount > $refundable) {
            throw InvalidRefundOperationException::because('Refund amount exceeds the remaining refundable amount.');
        }

        return $amount;
    }

    /**
     * @param  array<int|string, int>  $lines
     * @return Collection<int, int>
     */
    private function lineQuantities(Order $order, array $lines): Collection
    {
        return collect($lines)
            ->mapWithKeys(fn (mixed $quantity, int|string $lineId): array => [(int) $lineId => (int) $quantity])
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->each(function (int $quantity, int $lineId) use ($order): void {
                $line = $order->lines->firstWhere('id', $lineId);

                if (! $line instanceof OrderLine) {
                    throw InvalidRefundOperationException::because('Refund line does not belong to this order.');
                }

                if ($quantity > $line->quantity) {
                    throw InvalidRefundOperationException::because('Refund quantity exceeds the ordered quantity.');
                }
            });
    }

    /**
     * @param  Collection<int, int>  $lineQuantities
     */
    private function lineRefundAmount(Order $order, Collection $lineQuantities): int
    {
        return $lineQuantities->reduce(function (int $total, int $quantity, int $lineId) use ($order): int {
            $line = $order->lines->firstWhere('id', $lineId);

            if (! $line instanceof OrderLine) {
                return $total;
            }

            return $total + (int) round($line->total_amount / $line->quantity * $quantity);
        }, 0);
    }

    /**
     * @param  Collection<int, int>  $lineQuantities
     */
    private function restock(Order $order, Collection $lineQuantities): void
    {
        $linesToRestock = $lineQuantities->isNotEmpty()
            ? $lineQuantities
            : $order->lines->mapWithKeys(fn (OrderLine $line): array => [$line->getKey() => $line->quantity]);

        $linesToRestock->each(function (int $quantity, int $lineId) use ($order): void {
            $line = $order->lines->firstWhere('id', $lineId);

            if (! $line instanceof OrderLine || $line->variant_id === null) {
                return;
            }

            $item = InventoryItem::withoutGlobalScopes()
                ->where('variant_id', $line->variant_id)
                ->first();

            if ($item instanceof InventoryItem) {
                $this->inventory->restock($item, $quantity);
            }
        });
    }

    private function updateOrderFinancialStatus(Order $order, Payment $payment): void
    {
        $totalRefunded = $order->refunds()
            ->where('status', '!=', RefundStatus::Failed->value)
            ->sum('amount');

        if ($totalRefunded >= $order->total_amount) {
            $order->forceFill([
                'status' => OrderStatus::Refunded,
                'financial_status' => FinancialStatus::Refunded,
            ])->save();

            $payment->forceFill([
                'status' => PaymentStatus::Refunded,
            ])->save();

            return;
        }

        $order->forceFill([
            'financial_status' => FinancialStatus::PartiallyRefunded,
        ])->save();
    }
}
