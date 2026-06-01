<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\FulfillmentResource;
use App\Http\Resources\Admin\OrderListResource;
use App\Http\Resources\Admin\OrderResource;
use App\Http\Resources\Admin\RefundResource;
use App\Models\Order;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Admin order REST API. Reads are store-scoped via the resolved `current_store`;
 * the route-level `ability` middleware enforces `read-orders` / `write-orders`
 * token scopes. All amounts are integers in minor units (cents).
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly FulfillmentService $fulfillments,
        private readonly RefundService $refunds,
    ) {}

    /**
     * List orders with filtering, sorting, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:pending,paid,fulfilled,cancelled,refunded'],
            'financial_status' => ['sometimes', 'string', 'in:pending,paid,partially_refunded,refunded'],
            'fulfillment_status' => ['sometimes', 'string', 'in:unfulfilled,partial,fulfilled'],
            'customer_id' => ['sometimes', 'integer'],
            'created_after' => ['sometimes', 'date'],
            'created_before' => ['sometimes', 'date'],
            'query' => ['sometimes', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'in:placed_at_desc,placed_at_asc,total_desc,total_asc'],
        ]);

        $query = Order::query()->withCount('lines')->with('customer');

        foreach (['status', 'financial_status', 'fulfillment_status', 'customer_id'] as $field) {
            if (isset($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }

        if (isset($validated['created_after'])) {
            $query->where('placed_at', '>=', $validated['created_after']);
        }

        if (isset($validated['created_before'])) {
            $query->where('placed_at', '<=', $validated['created_before']);
        }

        if (! empty($validated['query'])) {
            $term = '%'.$validated['query'].'%';
            $query->where(fn (Builder $inner) => $inner
                ->where('order_number', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        $this->applySort($query, $validated['sort'] ?? 'placed_at_desc');

        $perPage = (int) ($validated['per_page'] ?? 25);

        return OrderListResource::collection($query->paginate($perPage))->response();
    }

    /**
     * Get full order details.
     */
    public function show(Request $request, $store, int $orderId): JsonResponse
    {
        $order = $this->resolveOrder($orderId);

        return (new OrderResource($order->load([
            'customer',
            'lines',
            'payments',
            'fulfillments.lines',
            'refunds',
        ])))->response();
    }

    /**
     * Create a fulfillment (ship items).
     */
    public function storeFulfillment(Request $request, $store, int $orderId): JsonResponse
    {
        $order = $this->resolveOrder($orderId);

        $validated = $request->validate([
            'tracking_company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tracking_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.order_line_id' => ['required', 'integer'],
            'line_items.*.quantity' => ['required', 'integer', 'min:1'],
            'notify_customer' => ['sometimes', 'boolean'],
        ]);

        $lines = [];
        foreach ($validated['line_items'] as $item) {
            $lines[(int) $item['order_line_id']] = (int) $item['quantity'];
        }

        $tracking = [
            'tracking_company' => $validated['tracking_company'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
        ];

        try {
            $fulfillment = $this->fulfillments->create($order, $lines, $tracking);
        } catch (\App\Exceptions\FulfillmentGuardException $exception) {
            abort(response()->json([
                'message' => $exception->getMessage(),
                'error_code' => 'not_fulfillable',
            ], 409));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['line_items' => [$exception->getMessage()]]);
        }

        return (new FulfillmentResource($fulfillment->load('lines')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Create a refund (partial or full).
     */
    public function storeRefund(Request $request, $store, int $orderId): JsonResponse
    {
        $order = $this->resolveOrder($orderId);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'line_items' => ['sometimes', 'array'],
            'line_items.*.order_line_id' => ['required_with:line_items', 'integer'],
            'line_items.*.quantity' => ['required_with:line_items', 'integer', 'min:1'],
            'notify_customer' => ['sometimes', 'boolean'],
            'restock' => ['sometimes', 'boolean'],
        ]);

        $payment = $order->payments()->latest('id')->first();

        if ($payment === null) {
            abort(response()->json([
                'message' => 'This order has no payment to refund.',
                'error_code' => 'not_refundable',
            ], 409));
        }

        $lines = [];
        foreach ($validated['line_items'] ?? [] as $item) {
            $lines[(int) $item['order_line_id']] = (int) $item['quantity'];
        }

        try {
            $refund = $this->refunds->create(
                order: $order,
                payment: $payment,
                amount: (int) $validated['amount'],
                reason: $validated['reason'] ?? null,
                restock: (bool) ($validated['restock'] ?? false),
                lines: $lines,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount' => [$exception->getMessage()]]);
        }

        return (new RefundResource($refund))
            ->response()
            ->setStatusCode(201);
    }

    private function resolveOrder(int $orderId): Order
    {
        $order = Order::query()->find($orderId);

        if ($order === null) {
            abort(404, 'The requested resource was not found.');
        }

        return $order;
    }

    /**
     * @param  Builder<Order>  $query
     */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'placed_at_asc' => $query->orderBy('placed_at'),
            'total_desc' => $query->orderByDesc('total_amount'),
            'total_asc' => $query->orderBy('total_amount'),
            default => $query->orderByDesc('placed_at'),
        };
    }
}
