<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('viewAny', Order::class);

        $orders = Order::where('store_id', $store->id)
            ->with(['lines', 'customer'])
            ->orderByDesc('placed_at')
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, Store $store, Order $order): JsonResponse
    {
        $this->authorize('view', $store);
        abort_unless($order->store_id === $store->id, 404);
        $this->authorize('view', $order);

        $order->load(['lines', 'payments', 'fulfillments.lines', 'refunds', 'customer']);

        return response()->json($order);
    }

    public function createFulfillment(Request $request, Store $store, Order $order): JsonResponse
    {
        $this->authorize('view', $store);
        abort_unless($order->store_id === $store->id, 404);
        $this->authorize('update', $order);

        $validated = $request->validate([
            'tracking_company' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:500',
            'line_items' => 'required|array|min:1',
            'line_items.*.order_line_id' => 'required|integer|exists:order_lines,id',
            'line_items.*.quantity' => 'required|integer|min:1',
        ]);

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'tracking_company' => $validated['tracking_company'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);

        foreach ($validated['line_items'] as $lineItem) {
            FulfillmentLine::create([
                'fulfillment_id' => $fulfillment->id,
                'order_line_id' => $lineItem['order_line_id'],
                'quantity' => $lineItem['quantity'],
            ]);
        }

        return response()->json($fulfillment->load('lines'), 201);
    }

    public function createRefund(Request $request, Store $store, Order $order): JsonResponse
    {
        $this->authorize('view', $store);
        abort_unless($order->store_id === $store->id, 404);
        $this->authorize('update', $order);

        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $payment = $order->payments()->first();

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment?->id,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'] ?? '',
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        return response()->json($refund, 201);
    }
}
