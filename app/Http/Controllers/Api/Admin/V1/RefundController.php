<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreRefundRequest;
use App\Http\Resources\Api\Admin\RefundResource;
use App\Models\Order;
use App\Models\Refund;
use App\Services\RefundService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refunds,
        protected CurrentStore $currentStore,
    ) {}

    public function index(int $storeId, int $orderId): JsonResponse
    {
        $order = $this->findOrder($orderId);

        Gate::authorize('view', $order);

        return response()->json([
            'data' => RefundResource::collection($order->refunds()->orderByDesc('id')->get()),
        ]);
    }

    public function store(StoreRefundRequest $request, int $storeId, int $orderId): JsonResponse
    {
        $order = $this->findOrder($orderId);

        Gate::authorize('create', [Refund::class, $order]);

        $validated = $request->validated();
        $lineItems = null;

        if (! empty($validated['line_items'])) {
            $lineItems = [];

            foreach ($validated['line_items'] as $item) {
                $lineItems[(int) $item['order_line_id']] = (int) $item['quantity'];
            }
        }

        $refund = $this->refunds->create(
            $order,
            $validated['amount'],
            $lineItems,
            $validated['reason'] ?? null,
            (bool) ($validated['restock'] ?? false),
        );

        return (new RefundResource($refund))->response()->setStatusCode(201);
    }

    protected function findOrder(int $orderId): Order
    {
        return Order::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($orderId)
            ->firstOrFail();
    }
}
