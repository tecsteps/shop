<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Exceptions\InvalidFulfillmentTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreFulfillmentRequest;
use App\Http\Resources\Api\Admin\FulfillmentResource;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class FulfillmentController extends Controller
{
    public function __construct(
        protected FulfillmentService $fulfillments,
        protected CurrentStore $currentStore,
    ) {}

    public function store(StoreFulfillmentRequest $request, int $storeId, int $orderId): JsonResponse
    {
        $order = $this->findOrder($orderId);

        Gate::authorize('create', [Fulfillment::class, $order]);

        $validated = $request->validated();
        $lines = [];

        foreach ($validated['line_items'] as $item) {
            $lines[(int) $item['order_line_id']] = (int) $item['quantity'];
        }

        $fulfillment = $this->fulfillments->create($order, $lines, [
            'tracking_company' => $validated['tracking_company'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
        ]);

        return (new FulfillmentResource($fulfillment->load('fulfillmentLines')))->response()->setStatusCode(201);
    }

    public function ship(int $storeId, int $orderId, int $fulfillmentId): JsonResponse
    {
        $fulfillment = $this->findFulfillment($orderId, $fulfillmentId);

        Gate::authorize('ship', $fulfillment);

        try {
            $fulfillment = $this->fulfillments->markAsShipped($fulfillment);
        } catch (InvalidFulfillmentTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new FulfillmentResource($fulfillment->load('fulfillmentLines')))->response();
    }

    public function deliver(int $storeId, int $orderId, int $fulfillmentId): JsonResponse
    {
        $fulfillment = $this->findFulfillment($orderId, $fulfillmentId);

        Gate::authorize('deliver', $fulfillment);

        try {
            $fulfillment = $this->fulfillments->markAsDelivered($fulfillment);
        } catch (InvalidFulfillmentTransitionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new FulfillmentResource($fulfillment->load('fulfillmentLines')))->response();
    }

    protected function findOrder(int $orderId): Order
    {
        return Order::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($orderId)
            ->firstOrFail();
    }

    protected function findFulfillment(int $orderId, int $fulfillmentId): Fulfillment
    {
        $order = $this->findOrder($orderId);

        return $order->fulfillments()->whereKey($fulfillmentId)->firstOrFail();
    }
}
