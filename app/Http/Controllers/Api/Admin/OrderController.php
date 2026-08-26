<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly FulfillmentService $fulfillmentService,
        private readonly RefundService $refundService,
    ) {}

    public function index(Request $request, int $storeId)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->when($request->input('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->financial_status, fn ($q) => $q->where('financial_status', $request->financial_status))
            ->when($request->fulfillment_status, fn ($q) => $q->where('fulfillment_status', $request->fulfillment_status))
            ->when($request->input('query'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->input('query').'%')
                    ->orWhere('email', 'like', '%'.$request->input('query').'%');
            }))
            ->paginate($request->per_page ?? 25);

        return response()->json([
            'data' => $orders->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'financial_status' => $order->financial_status,
                'fulfillment_status' => $order->fulfillment_status,
                'currency' => $order->currency,
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'placed_at' => $order->placed_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(int $storeId, int $orderId)
    {
        $order = Order::with(['lines', 'payments', 'fulfillments', 'refunds', 'customer'])->findOrFail($orderId);
        $this->authorize('view', $order);

        return response()->json(['data' => [
            'id' => $order->id,
            'store_id' => $order->store_id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'financial_status' => $order->financial_status,
            'fulfillment_status' => $order->fulfillment_status,
            'currency' => $order->currency,
            'subtotal_amount' => $order->subtotal_amount,
            'discount_amount' => $order->discount_amount,
            'shipping_amount' => $order->shipping_amount,
            'tax_amount' => $order->tax_amount,
            'total_amount' => $order->total_amount,
            'lines' => $order->lines,
            'payments' => $order->payments,
            'fulfillments' => $order->fulfillments,
            'refunds' => $order->refunds,
        ]]);
    }

    public function fulfill(Request $request, int $storeId, int $orderId)
    {
        $order = Order::findOrFail($orderId);
        $this->authorize('createFulfillment', $order);

        $validated = $request->validate([
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.order_line_id' => ['required', 'integer'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'tracking_company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        $fulfillment = $this->fulfillmentService->create($order, $validated['line_items'], [
            'tracking_company' => $validated['tracking_company'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
        ]);

        return response()->json(['data' => ['id' => $fulfillment->id, 'order_id' => $order->id, 'status' => $fulfillment->status]], 201);
    }

    public function refund(Request $request, int $storeId, int $orderId)
    {
        $order = Order::with('payments')->findOrFail($orderId);
        $this->authorize('createRefund', $order);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $payment = $order->payments()->firstOrFail();

        $refund = $this->refundService->create($order, $payment, $validated['amount'], $validated['reason'] ?? null, false);

        return response()->json(['data' => ['id' => $refund->id, 'order_id' => $order->id, 'amount' => $refund->amount, 'status' => $refund->status]], 201);
    }
}
