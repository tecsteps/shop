<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\FulfillmentLine;
use App\Models\InventoryItem;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends AdminController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', '');

        $orders = Order::query()
            ->with('customer')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('order_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '' && in_array($status, $this->enumValues(OrderStatus::class), true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'statuses' => OrderStatus::cases(),
            'currency' => $this->currentStore()->default_currency,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'lines.variant.inventoryItem',
            'payments.refunds',
            'fulfillments.lines.orderLine',
            'refunds.payment',
        ]);

        $lineIds = $order->lines->pluck('id');
        $fulfilledByLine = collect();

        if ($lineIds->isNotEmpty()) {
            $fulfilledByLine = FulfillmentLine::query()
                ->whereIn('order_line_id', $lineIds)
                ->selectRaw('order_line_id, SUM(quantity) as fulfilled_quantity')
                ->groupBy('order_line_id')
                ->pluck('fulfilled_quantity', 'order_line_id');
        }

        $maxRefundable = max($order->total_amount - (int) $order->refunds->sum('amount'), 0);

        return view('admin.orders.show', [
            'order' => $order,
            'currency' => $order->currency,
            'fulfilledByLine' => $fulfilledByLine,
            'maxRefundable' => $maxRefundable,
        ]);
    }

    public function confirmBankTransfer(Order $order): RedirectResponse
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer || $order->financial_status !== FinancialStatus::Pending) {
            return back()->withErrors([
                'confirm_payment' => 'This order is not waiting for bank transfer confirmation.',
            ]);
        }

        DB::transaction(function () use ($order): void {
            $updatedPayments = $order->payments()
                ->where('method', PaymentMethod::BankTransfer->value)
                ->where('status', PaymentStatus::Pending->value)
                ->update([
                    'status' => PaymentStatus::Captured->value,
                    'updated_at' => now(),
                ]);

            if ($updatedPayments === 0) {
                $order->payments()->create([
                    'provider' => 'mock',
                    'method' => PaymentMethod::BankTransfer->value,
                    'status' => PaymentStatus::Captured->value,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ]);
            }

            $order->update([
                'financial_status' => FinancialStatus::Paid->value,
                'status' => OrderStatus::Paid->value,
            ]);

            $order->load('lines.variant');

            if ($this->canAutoFulfillDigitalOrder($order)) {
                $fulfillment = $order->fulfillments()->create([
                    'status' => FulfillmentShipmentStatus::Delivered->value,
                    'shipped_at' => now(),
                    'delivered_at' => now(),
                ]);

                foreach ($order->lines as $line) {
                    $fulfillment->lines()->create([
                        'order_line_id' => $line->id,
                        'quantity' => $line->quantity,
                    ]);
                }

                $order->update([
                    'fulfillment_status' => FulfillmentStatus::Fulfilled->value,
                    'status' => OrderStatus::Fulfilled->value,
                ]);
            }
        });

        return back()->with('status', 'Bank transfer payment confirmed.');
    }

    public function storeFulfillment(Request $request, Order $order): RedirectResponse
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            return back()->withErrors([
                'fulfillment' => 'Fulfillment requires a paid or partially refunded financial status.',
            ]);
        }

        $validated = $request->validate([
            'lines' => ['nullable', 'array'],
            'lines.*' => ['nullable', 'integer', 'min:0'],
            'tracking_company' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
        ]);

        $order->load('lines');

        $lineIds = $order->lines->pluck('id');
        $fulfilledByLine = collect();

        if ($lineIds->isNotEmpty()) {
            $fulfilledByLine = FulfillmentLine::query()
                ->whereIn('order_line_id', $lineIds)
                ->selectRaw('order_line_id, SUM(quantity) as fulfilled_quantity')
                ->groupBy('order_line_id')
                ->pluck('fulfilled_quantity', 'order_line_id');
        }

        $requestedLines = collect($validated['lines'] ?? []);
        $customSelection = array_key_exists('lines', $validated);

        $linesToFulfill = [];

        foreach ($order->lines as $line) {
            $fulfilledQty = (int) ($fulfilledByLine[$line->id] ?? 0);
            $remainingQty = max($line->quantity - $fulfilledQty, 0);

            $requestedQty = $customSelection
                ? (int) ($requestedLines[$line->id] ?? 0)
                : $remainingQty;

            if ($requestedQty > $remainingQty) {
                throw ValidationException::withMessages([
                    "lines.{$line->id}" => "Requested quantity exceeds remaining quantity for {$line->title_snapshot}.",
                ]);
            }

            if ($requestedQty > 0) {
                $linesToFulfill[$line->id] = $requestedQty;
            }
        }

        if ($linesToFulfill === []) {
            return back()->withErrors([
                'fulfillment' => 'Select at least one line quantity to fulfill.',
            ]);
        }

        DB::transaction(function () use ($order, $validated, $linesToFulfill): void {
            $fulfillment = $order->fulfillments()->create([
                'status' => FulfillmentShipmentStatus::Pending->value,
                'tracking_company' => $validated['tracking_company'],
                'tracking_number' => $validated['tracking_number'],
                'tracking_url' => $validated['tracking_url'],
            ]);

            foreach ($linesToFulfill as $orderLineId => $quantity) {
                $fulfillment->lines()->create([
                    'order_line_id' => $orderLineId,
                    'quantity' => $quantity,
                ]);
            }

            $this->refreshOrderFulfillmentStatus($order->fresh());
        });

        return back()->with('status', 'Fulfillment created.');
    }

    public function storeRefund(Request $request, Order $order): RedirectResponse
    {
        if (! in_array($order->financial_status, [FinancialStatus::Paid, FinancialStatus::PartiallyRefunded], true)) {
            return back()->withErrors([
                'refund' => 'Refund requires a paid or partially refunded financial status.',
            ]);
        }

        $validated = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'restock' => ['nullable', 'boolean'],
            'lines' => ['nullable', 'array'],
            'lines.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $alreadyRefunded = (int) $order->refunds()->sum('amount');
        $refundable = max($order->total_amount - $alreadyRefunded, 0);

        if ($refundable <= 0) {
            return back()->withErrors([
                'refund' => 'No refundable amount remains for this order.',
            ]);
        }

        $amount = (int) ($validated['amount'] ?? $refundable);

        if ($amount > $refundable) {
            return back()->withErrors([
                'refund' => 'Refund amount exceeds remaining refundable total.',
            ]);
        }

        $payment = $order->payments()
            ->whereIn('status', [PaymentStatus::Captured->value, PaymentStatus::Refunded->value])
            ->latest('id')
            ->first();

        if (! $payment) {
            $payment = $order->payments()->create([
                'provider' => 'mock',
                'method' => $order->payment_method->value,
                'status' => PaymentStatus::Captured->value,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
            ]);
        }

        DB::transaction(function () use ($order, $payment, $validated, $amount): void {
            $order->refunds()->create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reason' => $validated['reason'],
                'status' => RefundStatus::Processed->value,
                'provider_refund_id' => sprintf('mock_refund_%d_%d', $order->id, now()->timestamp),
            ]);

            $totalRefunded = (int) $order->refunds()->sum('amount');

            if ($totalRefunded >= $order->total_amount) {
                $order->update([
                    'financial_status' => FinancialStatus::Refunded->value,
                    'status' => OrderStatus::Refunded->value,
                ]);
            } else {
                $order->update([
                    'financial_status' => FinancialStatus::PartiallyRefunded->value,
                ]);
            }

            $payment->update([
                'status' => $totalRefunded >= $payment->amount
                    ? PaymentStatus::Refunded->value
                    : PaymentStatus::Captured->value,
            ]);

            if (($validated['restock'] ?? false) && ! empty($validated['lines'])) {
                $this->restockRefundedLines($order, $validated['lines']);
            }
        });

        return back()->with('status', 'Refund created.');
    }

    protected function canAutoFulfillDigitalOrder(Order $order): bool
    {
        if ($order->lines->isEmpty()) {
            return false;
        }

        foreach ($order->lines as $line) {
            if ($line->variant_id === null) {
                return false;
            }

            if (! $line->variant || $line->variant->requires_shipping) {
                return false;
            }
        }

        return true;
    }

    protected function refreshOrderFulfillmentStatus(Order $order): void
    {
        $lineIds = $order->lines()->pluck('id');

        if ($lineIds->isEmpty()) {
            return;
        }

        $totalQuantity = (int) $order->lines()->sum('quantity');
        $fulfilledQuantity = (int) FulfillmentLine::query()
            ->whereIn('order_line_id', $lineIds)
            ->sum('quantity');

        if ($fulfilledQuantity <= 0) {
            $fulfillmentStatus = FulfillmentStatus::Unfulfilled;
        } elseif ($fulfilledQuantity < $totalQuantity) {
            $fulfillmentStatus = FulfillmentStatus::Partial;
        } else {
            $fulfillmentStatus = FulfillmentStatus::Fulfilled;
        }

        $update = [
            'fulfillment_status' => $fulfillmentStatus->value,
        ];

        if ($fulfillmentStatus === FulfillmentStatus::Fulfilled
            && ! in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            $update['status'] = OrderStatus::Fulfilled->value;
        }

        $order->update($update);
    }

    /**
     * @param  array<int|string, int|string|null>  $linePayload
     */
    protected function restockRefundedLines(Order $order, array $linePayload): void
    {
        $normalized = [];

        foreach ($linePayload as $orderLineId => $quantity) {
            $qty = (int) $quantity;

            if ($qty > 0) {
                $normalized[(int) $orderLineId] = $qty;
            }
        }

        if ($normalized === []) {
            return;
        }

        $lines = $order->lines()
            ->whereIn('id', array_keys($normalized))
            ->get()
            ->keyBy('id');

        foreach ($normalized as $orderLineId => $quantity) {
            $line = $lines->get($orderLineId);

            if (! $line || ! $line->variant_id) {
                continue;
            }

            InventoryItem::query()
                ->where('variant_id', $line->variant_id)
                ->increment('quantity_on_hand', $quantity);
        }
    }
}
