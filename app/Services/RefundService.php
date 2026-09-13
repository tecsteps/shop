<?php

namespace App\Services;

use App\Enums\FinancialStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Events\OrderRefunded;
use App\Exceptions\InvalidRefundException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Refund processing per spec 05 section 11.4.
 */
class RefundService
{
    public function __construct(
        protected PaymentService $payments,
        protected InventoryService $inventory,
    ) {}

    public function refundableAmount(Order $order): int
    {
        $refunded = (int) $order->refunds()->where('status', '!=', RefundStatus::Failed)->sum('amount');

        return max(0, $order->total_amount - $refunded);
    }

    /**
     * @param  array<int, int>|null  $lineItems  Map of order_line_id to quantity.
     */
    public function create(
        Order $order,
        int $amount,
        ?array $lineItems = null,
        ?string $reason = null,
        bool $restock = false,
    ): Refund {
        return DB::transaction(function () use ($order, $amount, $lineItems, $reason, $restock): Refund {
            $fresh = $order->refresh();

            if (! in_array($fresh->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
                throw new InvalidRefundException('This order cannot be refunded.', 409);
            }

            if ($amount < 1) {
                throw ValidationException::withMessages([
                    'amount' => ['The amount must be at least 1.'],
                ]);
            }

            $refundable = $this->refundableAmount($fresh);

            if ($amount > $refundable) {
                throw ValidationException::withMessages([
                    'amount' => ['The amount must not exceed the refundable total.'],
                ]);
            }

            $validatedLines = $this->validateLineItems($fresh, $lineItems);

            $payment = $fresh->payments()->where('status', PaymentStatus::Captured)->latest('id')->first()
                ?? $fresh->payments()->latest('id')->first();

            if ($payment === null) {
                throw new InvalidRefundException('This order has no payment to refund.', 409);
            }

            $result = $this->payments->refund($payment, $amount);

            $refund = $fresh->refunds()->create([
                'payment_id' => $payment->getKey(),
                'amount' => $amount,
                'reason' => $reason,
                'status' => RefundStatus::Pending,
                'provider_refund_id' => $result->providerRefundId,
            ]);

            if ($result->success) {
                $refund->status = RefundStatus::Processed;
                $refund->save();
            } else {
                $refund->status = RefundStatus::Failed;
                $refund->save();

                throw new InvalidRefundException('The payment provider declined the refund.', 502);
            }

            $totalRefunded = (int) $fresh->refunds()->where('status', '!=', RefundStatus::Failed)->sum('amount');

            if ($totalRefunded >= $fresh->total_amount) {
                $fresh->financial_status = FinancialStatus::Refunded;
                $fresh->status = OrderStatus::Refunded;
                $payment->status = PaymentStatus::Refunded;
                $payment->save();
            } else {
                $fresh->financial_status = FinancialStatus::PartiallyRefunded;
            }

            $fresh->save();

            if ($restock) {
                $this->restock($fresh, $validatedLines, $amount >= $fresh->total_amount);
            }

            event(new OrderRefunded($fresh->refresh(), $refund->refresh()));

            return $refund->refresh();
        });
    }

    /**
     * @param  array<int, int>|null  $lineItems
     * @return array<int, int>
     */
    protected function validateLineItems(Order $order, ?array $lineItems): array
    {
        if ($lineItems === null || $lineItems === []) {
            return [];
        }

        $orderLines = $order->orderLines()->get()->keyBy('id');
        $validated = [];

        foreach ($lineItems as $orderLineId => $quantity) {
            $orderLine = $orderLines->get((int) $orderLineId);

            if ($orderLine === null) {
                throw ValidationException::withMessages([
                    'line_items' => ['One or more line items do not belong to this order.'],
                ]);
            }

            if ($quantity < 1 || $quantity > $orderLine->quantity) {
                throw ValidationException::withMessages([
                    'line_items' => ['The quantity must be between 1 and the ordered quantity.'],
                ]);
            }

            $validated[(int) $orderLineId] = (int) $quantity;
        }

        return $validated;
    }

    /**
     * @param  array<int, int>  $validatedLines
     */
    protected function restock(Order $order, array $validatedLines, bool $isFullRefund): void
    {
        $targets = $validatedLines;

        if ($targets === [] && $isFullRefund) {
            foreach ($order->orderLines()->get() as $line) {
                $targets[$line->getKey()] = $line->quantity;
            }
        }

        if ($targets === []) {
            return;
        }

        $orderLines = $order->orderLines()->get()->keyBy('id');

        foreach ($targets as $orderLineId => $quantity) {
            $line = $orderLines->get($orderLineId);

            if ($line === null || $line->variant_id === null) {
                continue;
            }

            $item = InventoryItem::query()
                ->where('store_id', $order->store_id)
                ->where('variant_id', $line->variant_id)
                ->first();

            if ($item !== null) {
                $this->inventory->restock($item, $quantity);
            }
        }
    }
}
