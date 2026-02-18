<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\CreateFulfillmentRequest;
use App\Http\Requests\Api\Admin\CreateRefundRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Store;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private FulfillmentService $fulfillmentService,
        private RefundService $refundService,
    ) {}

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $query = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('financial_status')) {
            $query->where('financial_status', $request->input('financial_status'));
        }

        if ($request->filled('fulfillment_status')) {
            $query->where('fulfillment_status', $request->input('fulfillment_status'));
        }

        if ($request->filled('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $orders = $query->orderBy('placed_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Store $store, int $order): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $orderModel = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($order);

        return (new OrderResource($orderModel))->response();
    }

    public function createFulfillment(CreateFulfillmentRequest $request, Store $store, int $order): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $orderModel = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($order);

        /** @var array<int, array{order_line_id: int, quantity: int}> $lineItems */
        $lineItems = $request->validated('line_items');

        $lines = [];
        foreach ($lineItems as $item) {
            $lines[(int) $item['order_line_id']] = (int) $item['quantity'];
        }

        $trackingData = [
            'tracking_company' => $request->validated('tracking_company'),
            'tracking_number' => $request->validated('tracking_number'),
            'tracking_url' => $request->validated('tracking_url'),
        ];

        try {
            $fulfillment = $this->fulfillmentService->create($orderModel, $lines, $trackingData);
            $this->fulfillmentService->markAsShipped($fulfillment, $trackingData);
        } catch (\App\Exceptions\FulfillmentGuardException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $fulfillment->id,
                'order_id' => $orderModel->id,
                'status' => $fulfillment->status?->value ?? $fulfillment->status,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
                'line_items' => collect($lineItems)->map(fn ($item) => [
                    'order_line_id' => $item['order_line_id'],
                    'quantity' => $item['quantity'],
                ]),
            ],
        ], 201);
    }

    public function createRefund(CreateRefundRequest $request, Store $store, int $order): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $orderModel = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($order);

        try {
            $refund = $this->refundService->create(
                $orderModel,
                (int) $request->validated('amount'),
                $request->validated('reason'),
                (bool) $request->validated('restock', false),
            );
        } catch (\App\Exceptions\RefundExceedsPaymentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $refund->id,
                'order_id' => $orderModel->id,
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status?->value ?? $refund->status,
                'created_at' => $refund->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $hasAccess = $user->stores()->where('stores.id', $store->id)->exists();

        if (! $hasAccess) {
            abort(403, 'You do not have access to this store.');
        }

        app()->instance('current_store', $store);
    }
}
