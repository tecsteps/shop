<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\OrderService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(
        private readonly FulfillmentService $fulfillments,
        private readonly RefundService $refunds,
        private readonly OrderService $orders,
    ) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:pending,paid,fulfilled,cancelled,refunded'],
            'financial_status' => ['sometimes', 'in:pending,authorized,paid,partially_refunded,refunded,voided'],
            'fulfillment_status' => ['sometimes', 'in:unfulfilled,partial,fulfilled'],
            'query' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'max:100'],
        ]);
        $orders = Order::withoutGlobalScopes()->where('store_id', $storeId)
            ->when(isset($data['status']), fn ($q) => $q->where('status', $data['status']))
            ->when(isset($data['financial_status']), fn ($q) => $q->where('financial_status', $data['financial_status']))
            ->when(isset($data['fulfillment_status']), fn ($q) => $q->where('fulfillment_status', $data['fulfillment_status']))
            ->when(isset($data['query']), fn ($q) => $q->where(fn ($nested) => $nested->where('order_number', 'like', '%'.$data['query'].'%')->orWhere('email', 'like', '%'.$data['query'].'%')))
            ->with('customer')->latest('placed_at')->paginate($data['per_page'] ?? 25);

        return response()->json(['data' => $orders->items(), 'meta' => ['total' => $orders->total(), 'current_page' => $orders->currentPage()]]);
    }

    public function show(int $storeId, int $orderId): JsonResponse
    {
        return response()->json(['data' => $this->find($storeId, $orderId)->load(['lines', 'payments', 'refunds', 'fulfillments.lines', 'customer'])]);
    }

    public function fulfill(Request $request, int $storeId, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['required', 'integer', 'min:1'],
            'tracking_company' => ['nullable', 'string'],
            'tracking_number' => ['nullable', 'string'],
            'tracking_url' => ['nullable', 'url'],
        ]);
        $tracking = collect($data)->only(['tracking_company', 'tracking_number', 'tracking_url'])->all();
        $fulfillment = $this->fulfillments->create($this->find($storeId, $orderId), $data['lines'], $tracking);

        return response()->json(['data' => $fulfillment], 201);
    }

    public function refund(Request $request, int $storeId, int $orderId): JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1'], 'reason' => ['nullable', 'string'], 'restock' => ['sometimes', 'boolean'], 'lines' => ['sometimes', 'array']]);
        $order = $this->find($storeId, $orderId)->load('payments');
        $refund = $this->refunds->create($order, $order->payments->firstOrFail(), $data['amount'], $data['reason'] ?? null, (bool) ($data['restock'] ?? false), $data['lines'] ?? null);

        return response()->json(['data' => $refund], 201);
    }

    public function confirmPayment(int $storeId, int $orderId): JsonResponse
    {
        $order = $this->find($storeId, $orderId);
        $this->orders->confirmBankTransfer($order);

        return response()->json(['data' => $order->refresh()]);
    }

    private function find(int $storeId, int $id): Order
    {
        return Order::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($id);
    }
}
