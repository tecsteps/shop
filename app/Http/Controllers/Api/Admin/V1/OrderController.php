<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, int $storeId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,paid,fulfilled,cancelled,refunded'],
            'financial_status' => ['nullable', 'string', 'in:pending,authorized,paid,partially_refunded,refunded,voided'],
            'fulfillment_status' => ['nullable', 'string', 'in:unfulfilled,partial,fulfilled'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'created_after' => ['nullable', 'date'],
            'created_before' => ['nullable', 'date'],
            'query' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string'],
        ]);

        $query = Order::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->with('customer')
            ->withCount('lines');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['financial_status'])) {
            $query->where('financial_status', $validated['financial_status']);
        }

        if (! empty($validated['fulfillment_status'])) {
            $query->where('fulfillment_status', $validated['fulfillment_status']);
        }

        if (! empty($validated['customer_id'])) {
            $query->where('customer_id', (int) $validated['customer_id']);
        }

        if (! empty($validated['created_after'])) {
            $query->where('placed_at', '>=', $validated['created_after']);
        }

        if (! empty($validated['created_before'])) {
            $query->where('placed_at', '<=', $validated['created_before']);
        }

        if (! empty($validated['query'])) {
            $needle = trim((string) $validated['query']);
            $query->where(function ($builder) use ($needle): void {
                $builder
                    ->where('order_number', 'like', "%{$needle}%")
                    ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        $this->applySort($query, $validated['sort'] ?? 'placed_at_desc');

        $perPage = (int) ($validated['per_page'] ?? 25);
        $orders = $query->paginate($perPage)->appends($request->query());

        $data = $orders->getCollection()->map(function (Order $order): array {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'financial_status' => $order->financial_status->value,
                'fulfillment_status' => $order->fulfillment_status->value,
                'customer' => $order->customer
                    ? [
                        'id' => $order->customer->id,
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                    ]
                    : null,
                'currency' => $order->currency,
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'line_count' => $order->lines_count,
                'placed_at' => $order->placed_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(int $storeId, int $orderId): JsonResponse
    {
        $order = Order::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->with([
                'customer',
                'lines',
                'payments',
                'fulfillments',
                'refunds',
            ])
            ->find($orderId);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $order->id,
                'store_id' => $order->store_id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'financial_status' => $order->financial_status->value,
                'fulfillment_status' => $order->fulfillment_status->value,
                'customer' => $order->customer
                    ? [
                        'id' => $order->customer->id,
                        'name' => $order->customer->name,
                        'email' => $order->customer->email,
                    ]
                    : null,
                'email' => $order->email,
                'currency' => $order->currency,
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'billing_address_json' => $order->billing_address_json,
                'shipping_address_json' => $order->shipping_address_json,
                'lines' => $order->lines->map(fn ($line): array => [
                    'id' => $line->id,
                    'product_id' => $line->product_id,
                    'variant_id' => $line->variant_id,
                    'title_snapshot' => $line->title_snapshot,
                    'sku_snapshot' => $line->sku_snapshot,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->total_amount,
                    'tax_lines_json' => $line->tax_lines_json,
                    'discount_allocations_json' => $line->discount_allocations_json,
                ])->values()->all(),
                'payments' => $order->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'provider' => $payment->provider,
                    'method' => $payment->method->value,
                    'provider_payment_id' => $payment->provider_payment_id,
                    'status' => $payment->status->value,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])->values()->all(),
                'fulfillments' => $order->fulfillments->map(fn ($fulfillment): array => [
                    'id' => $fulfillment->id,
                    'status' => $fulfillment->status->value,
                    'tracking_company' => $fulfillment->tracking_company,
                    'tracking_number' => $fulfillment->tracking_number,
                    'tracking_url' => $fulfillment->tracking_url,
                    'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
                    'delivered_at' => $fulfillment->delivered_at?->toIso8601String(),
                ])->values()->all(),
                'refunds' => $order->refunds->map(fn ($refund): array => [
                    'id' => $refund->id,
                    'payment_id' => $refund->payment_id,
                    'amount' => $refund->amount,
                    'reason' => $refund->reason,
                    'status' => $refund->status->value,
                    'provider_refund_id' => $refund->provider_refund_id,
                    'created_at' => $refund->created_at?->toIso8601String(),
                ])->values()->all(),
                'placed_at' => $order->placed_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
            ],
        ]);
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'placed_at_asc' => $query->orderBy('placed_at'),
            'total_desc' => $query->orderByDesc('total_amount'),
            'total_asc' => $query->orderBy('total_amount'),
            default => $query->orderByDesc('placed_at'),
        };
    }
}
