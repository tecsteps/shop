<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentStatus;
use App\Exceptions\FulfillmentGuardException;
use App\Exceptions\FulfillmentQuantityException;
use App\Exceptions\RefundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListOrdersRequest;
use App\Http\Requests\Admin\StoreFulfillmentRequest;
use App\Http\Requests\Admin\StoreRefundRequest;
use App\Http\Resources\Admin\FulfillmentResource;
use App\Http\Resources\Admin\OrderResource;
use App\Http\Resources\Admin\RefundResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Services\FulfillmentService;
use App\Services\RefundService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(ListOrdersRequest $request, Store $store): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('customer', 'lines', 'payments', 'fulfillments.lines.orderLine', 'refunds.payment')
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['financial_status'] ?? null, fn (Builder $query, string $status) => $query->where('financial_status', $status))
            ->when($validated['fulfillment_status'] ?? null, fn (Builder $query, string $status) => $query->where('fulfillment_status', $status))
            ->when($validated['customer_id'] ?? null, fn (Builder $query, int $customerId) => $query->where('customer_id', $customerId))
            ->when($validated['created_after'] ?? null, fn (Builder $query, string $date) => $query->where('placed_at', '>=', $date))
            ->when($validated['created_before'] ?? null, fn (Builder $query, string $date) => $query->where('placed_at', '<=', $date))
            ->when($validated['query'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('order_number', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%')
                        ->orWhereHas('customer', fn (Builder $query) => $query->where('email', 'like', '%'.$term.'%'));
                });
            });

        $this->applySort($query, $validated['sort'] ?? 'placed_at_desc');

        return OrderResource::collection(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function show(Store $store, int $order): OrderResource
    {
        return new OrderResource($this->loadOrder($this->findOrder($store, $order)));
    }

    public function storeFulfillment(StoreFulfillmentRequest $request, Store $store, int $order, FulfillmentService $fulfillments): JsonResponse
    {
        $existingOrder = $this->loadOrder($this->findOrder($store, $order));
        $validated = $request->validated();

        try {
            $fulfillment = $fulfillments->create(
                $existingOrder,
                $this->lineItemMap($validated['line_items']),
                Arr::only($validated, ['tracking_company', 'tracking_number', 'tracking_url']),
            );
            $fulfillments->markAsShipped($fulfillment, Arr::only($validated, ['tracking_company', 'tracking_number', 'tracking_url']));
        } catch (FulfillmentQuantityException $exception) {
            throw ValidationException::withMessages([
                'line_items' => [$exception->getMessage()],
            ]);
        } catch (FulfillmentGuardException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return (new FulfillmentResource($fulfillment->refresh()->load('lines.orderLine')))
            ->response()
            ->setStatusCode(201);
    }

    public function storeRefund(StoreRefundRequest $request, Store $store, int $order, RefundService $refunds): JsonResponse
    {
        $existingOrder = $this->loadOrder($this->findOrder($store, $order));
        $payment = $existingOrder->payments
            ->where('status', PaymentStatus::Captured)
            ->sortByDesc('created_at')
            ->first();

        if (! $payment instanceof Payment) {
            return response()->json(['message' => 'Order has no captured payment to refund.'], 409);
        }

        $validated = $request->validated();

        try {
            $refund = $refunds->create(
                $existingOrder,
                $payment,
                (int) $validated['amount'],
                $validated['reason'] ?? null,
            );
        } catch (RefundException $exception) {
            $status = $exception->errorCode === 'invalid_refund_amount' ? 422 : 409;

            return response()->json([
                'message' => $exception->getMessage(),
                'error_code' => $exception->errorCode,
            ], $status);
        }

        return (new RefundResource($refund->load('payment')))
            ->response()
            ->setStatusCode(201);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'placed_at_asc' => $query->orderBy('placed_at'),
            'total_desc' => $query->orderByDesc('total_amount'),
            'total_asc' => $query->orderBy('total_amount'),
            default => $query->latest('placed_at'),
        };
    }

    private function findOrder(Store $store, int $orderId): Order
    {
        return Order::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($orderId)
            ->firstOrFail();
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load('customer', 'lines', 'payments', 'fulfillments.lines.orderLine', 'refunds.payment');
    }

    /**
     * @param  array<int, array{order_line_id: int, quantity: int}>  $lineItems
     * @return array<int, int>
     */
    private function lineItemMap(array $lineItems): array
    {
        return collect($lineItems)
            ->mapWithKeys(fn (array $line): array => [(int) $line['order_line_id'] => (int) $line['quantity']])
            ->all();
    }
}
