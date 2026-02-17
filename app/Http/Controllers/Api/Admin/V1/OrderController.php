<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Exceptions\FulfillmentGuardException;
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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private FulfillmentService $fulfillmentService,
        private RefundService $refundService,
    ) {}

    public function index(Request $request, int $storeId): AnonymousResourceCollection
    {
        $this->authorizeAbility($request, 'read-orders');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $query = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('financial_status')) {
            $query->where('financial_status', $request->input('financial_status'));
        }

        if ($request->filled('fulfillment_status')) {
            $query->where('fulfillment_status', $request->input('fulfillment_status'));
        }

        $sort = $request->input('sort', 'placed_at_desc');
        $query = match ($sort) {
            'placed_at_asc' => $query->orderBy('placed_at'),
            'total_desc' => $query->orderByDesc('total_amount'),
            'total_asc' => $query->orderBy('total_amount'),
            default => $query->orderByDesc('placed_at'),
        };

        $perPage = min((int) $request->input('per_page', 25), 100);

        return OrderResource::collection(
            $query->paginate($perPage)
        );
    }

    public function show(Request $request, int $storeId, int $orderId): OrderResource
    {
        $this->authorizeAbility($request, 'read-orders');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $order = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('customer', 'lines', 'payments', 'fulfillments', 'refunds')
            ->findOrFail($orderId);

        return new OrderResource($order);
    }

    public function createFulfillment(CreateFulfillmentRequest $request, int $storeId, int $orderId): JsonResponse
    {
        $this->authorizeAbility($request, 'write-orders');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $order = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($orderId);

        $lines = [];
        foreach ($request->input('line_items') as $item) {
            $lines[$item['order_line_id']] = $item['quantity'];
        }

        $tracking = array_filter([
            'tracking_company' => $request->input('tracking_company'),
            'tracking_number' => $request->input('tracking_number'),
            'tracking_url' => $request->input('tracking_url'),
        ]);

        try {
            $fulfillment = $this->fulfillmentService->create(
                $order,
                $lines,
                $tracking ?: null,
            );
        } catch (FulfillmentGuardException) {
            return response()->json([
                'message' => 'Order is not in a fulfillable state.',
            ], 409);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['line_items' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'data' => [
                'id' => $fulfillment->id,
                'order_id' => $fulfillment->order_id,
                'status' => $fulfillment->status->value,
                'tracking_company' => $fulfillment->tracking_company,
                'tracking_number' => $fulfillment->tracking_number,
                'tracking_url' => $fulfillment->tracking_url,
                'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
                'line_items' => collect($request->input('line_items'))->map(fn ($item) => [
                    'order_line_id' => $item['order_line_id'],
                    'quantity' => $item['quantity'],
                ])->toArray(),
            ],
        ], 201);
    }

    public function createRefund(CreateRefundRequest $request, int $storeId, int $orderId): JsonResponse
    {
        $this->authorizeAbility($request, 'write-orders');

        $store = Store::findOrFail($storeId);
        $this->authorizeStoreAccess($request, $store);

        $order = Order::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('payments')
            ->findOrFail($orderId);

        $payment = $order->payments->first();

        if (! $payment) {
            return response()->json([
                'message' => 'No payment found for this order.',
            ], 409);
        }

        try {
            $refund = $this->refundService->create(
                $order,
                $payment,
                $request->integer('amount'),
                $request->input('reason'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['amount' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'data' => [
                'id' => $refund->id,
                'order_id' => $refund->order_id,
                'payment_id' => $refund->payment_id,
                'provider_refund_id' => $refund->provider_refund_id,
                'amount' => $refund->amount,
                'reason' => $refund->reason,
                'status' => $refund->status->value,
                'created_at' => $refund->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    private function authorizeAbility(Request $request, string $ability): void
    {
        $user = $request->user();

        if (! $user || ! $user->tokenCan($ability)) {
            abort(403, 'Insufficient permissions.');
        }
    }

    private function authorizeStoreAccess(Request $request, Store $store): void
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        $hasAccess = $user->stores()->where('stores.id', $store->id)->exists();

        if (! $hasAccess) {
            abort(403, 'You do not have access to this store.');
        }

        app()->instance('current_store', $store);
    }
}
