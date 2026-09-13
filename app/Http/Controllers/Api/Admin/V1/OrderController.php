<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Admin\OrderResource;
use App\Http\Resources\Api\Admin\OrderSummaryResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected CurrentStore $currentStore,
    ) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'status' => 'nullable|in:pending,paid,fulfilled,cancelled,refunded',
            'financial_status' => 'nullable|in:pending,authorized,paid,partially_refunded,refunded,voided',
            'fulfillment_status' => 'nullable|in:unfulfilled,partial,fulfilled',
            'customer_id' => 'nullable|integer|min:1',
            'created_after' => 'nullable|date',
            'created_before' => 'nullable|date',
            'query' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|in:placed_at_desc,placed_at_asc,total_desc,total_asc',
        ]);

        $query = Order::query()
            ->where('store_id', $this->currentStore->id())
            ->with('customer')
            ->withCount('orderLines');

        foreach (['status', 'financial_status', 'fulfillment_status', 'customer_id'] as $filter) {
            if (! empty($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        if (! empty($validated['created_after'])) {
            $query->where('placed_at', '>=', $validated['created_after']);
        }

        if (! empty($validated['created_before'])) {
            $query->where('placed_at', '<=', $validated['created_before']);
        }

        if (! empty($validated['query'])) {
            $search = $validated['query'];
            $query->where(fn ($inner) => $inner
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        match ($validated['sort'] ?? 'placed_at_desc') {
            'placed_at_asc' => $query->orderBy('placed_at')->orderBy('id'),
            'total_desc' => $query->orderByDesc('total_amount')->orderBy('id'),
            'total_asc' => $query->orderBy('total_amount')->orderBy('id'),
            default => $query->orderByDesc('placed_at')->orderByDesc('id'),
        };

        $paginator = $query->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => OrderSummaryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $storeId, int $orderId): JsonResponse
    {
        $order = $this->findOrder($orderId);

        Gate::authorize('view', $order);

        return (new OrderResource($order))->response();
    }

    public function confirmPayment(int $storeId, int $orderId): JsonResponse
    {
        $order = $this->findOrder($orderId);

        Gate::authorize('confirmPayment', $order);

        try {
            $order = $this->orders->confirmBankTransferPayment($order);
        } catch (InvalidOrderTransitionException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return (new OrderResource($this->findOrder($order->getKey())))->response();
    }

    protected function findOrder(int $orderId): Order
    {
        return Order::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($orderId)
            ->with([
                'customer',
                'orderLines',
                'payments',
                'fulfillments.fulfillmentLines',
                'refunds',
            ])
            ->firstOrFail();
    }
}
